<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/../config.php");
cp3407_require_login();

$cart = $_SESSION['cart'] ?? [];
$restaurant_id = (int)($_SESSION['restaurant_id'] ?? 0);

if (empty($cart) || !is_array($cart)) {
    die("Your cart is empty.");
}

if ($restaurant_id <= 0) {
    die("Invalid restaurant.");
}

$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$rider_id = (int)($_POST['rider_id'] ?? 0);

if ($phone === '' || $address === '') {
    die("Phone number and address are required.");
}

$user_id = cp3407_current_user_id($conn);
if ($user_id <= 0) {
    die("Unable to identify the logged-in user.");
}

$normalized_cart = [];
$subtotal = 0.00;

foreach ($cart as $cart_key => $item) {
    if (is_array($item)) {
        $menu_item_id = isset($item['id']) ? (int)$item['id'] : (int)$cart_key;
        $item_name = isset($item['name']) ? trim((string)$item['name']) : '';
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $price = isset($item['price']) ? (float)$item['price'] : 0.00;

        if ($menu_item_id <= 0 || $item_name === '' || $quantity <= 0) {
            die("Invalid cart item data.");
        }
    } else {
        $menu_item_id = (int)$cart_key;
        $quantity = (int)$item;

        if ($menu_item_id <= 0 || $quantity <= 0) {
            die("Invalid cart item structure.");
        }

        $stmt_lookup = $conn->prepare("
            SELECT id, name, price, restaurant_id
            FROM menu_items
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt_lookup) {
            die("Prepare failed (menu item lookup): " . $conn->error);
        }

        $stmt_lookup->bind_param("i", $menu_item_id);
        $stmt_lookup->execute();
        $lookup_result = $stmt_lookup->get_result();

        if ($lookup_result->num_rows === 0) {
            die("Menu item not found for cart item ID: " . $menu_item_id);
        }

        $db_item = $lookup_result->fetch_assoc();
        $item_name = trim((string)$db_item['name']);
        $price = (float)$db_item['price'];
    }

    $normalized_cart[$menu_item_id] = [
        'id' => $menu_item_id,
        'name' => $item_name,
        'price' => $price,
        'quantity' => $quantity
    ];

    $subtotal += ($price * $quantity);
}

$_SESSION['cart'] = $normalized_cart;

$delivery_fee = 3.99;
$service_fee = 1.50;
$total_price = $subtotal + $delivery_fee + $service_fee;

$platform_fee = round($subtotal * 0.10 + $service_fee, 2);
$merchant_amount = round($subtotal * 0.90, 2);
$rider_amount = round($delivery_fee, 2);

$order_status = 'pending';
$payment_status = 'pending';

$conn->begin_transaction();

try {
    $sql_order = "
        INSERT INTO orders (
            user_id,
            restaurant_id,
            rider_id,
            subtotal,
            delivery_fee,
            service_fee,
            total_price,
            platform_fee,
            merchant_amount,
            rider_amount,
            phone,
            delivery_address,
            notes,
            payment_status,
            order_status,
            status_updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt_order = $conn->prepare($sql_order);
    if (!$stmt_order) {
        throw new Exception("Prepare failed (orders): " . $conn->error);
    }

    $stmt_order->bind_param(
        "iiidddddddsssss",
        $user_id,
        $restaurant_id,
        $rider_id,
        $subtotal,
        $delivery_fee,
        $service_fee,
        $total_price,
        $platform_fee,
        $merchant_amount,
        $rider_amount,
        $phone,
        $address,
        $notes,
        $payment_status,
        $order_status
    );

    if (!$stmt_order->execute()) {
        throw new Exception("Execute failed (orders): " . $stmt_order->error);
    }

    $order_id = $stmt_order->insert_id;

    $sql_item = "
        INSERT INTO order_items (
            order_id,
            menu_item_id,
            item_name,
            quantity,
            price
        ) VALUES (?, ?, ?, ?, ?)
    ";

    $stmt_item = $conn->prepare($sql_item);
    if (!$stmt_item) {
        throw new Exception("Prepare failed (order_items): " . $conn->error);
    }

    foreach ($normalized_cart as $item) {
        $menu_item_id = (int)$item['id'];
        $item_name = trim((string)$item['name']);
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];

        if ($menu_item_id <= 0 || $item_name === '' || $quantity <= 0) {
            throw new Exception("Invalid cart item data.");
        }

        $stmt_item->bind_param("iisid", $order_id, $menu_item_id, $item_name, $quantity, $price);

        if (!$stmt_item->execute()) {
            throw new Exception("Execute failed (order_items): " . $stmt_item->error);
        }
    }

    $sql_history = "
        INSERT INTO order_status_history (
            order_id,
            status,
            notes,
            updated_by
        ) VALUES (?, ?, ?, ?)
    ";

    $stmt_history = $conn->prepare($sql_history);
    if (!$stmt_history) {
        throw new Exception("Prepare failed (order_status_history): " . $conn->error);
    }

    $history_status = 'pending';
    $history_note = 'Order created and waiting for payment.';
    $updated_by = 'system';

    $stmt_history->bind_param("isss", $order_id, $history_status, $history_note, $updated_by);

    if (!$stmt_history->execute()) {
        throw new Exception("Execute failed (order_status_history): " . $stmt_history->error);
    }

    $conn->commit();

    $_SESSION['last_order_id'] = $order_id;

    header("Location: /CP3407/Online%20Payment/create_checkout.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die($e->getMessage());
}
?>