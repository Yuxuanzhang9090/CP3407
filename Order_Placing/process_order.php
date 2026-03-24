<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../config.php");


/* Read cart and restaurant from session */
$cart = $_SESSION['cart'] ?? [];
$restaurant_id = $_SESSION['restaurant_id'] ?? 0;

if (empty($cart)) {
    die("Your cart is empty.");
}

if ($restaurant_id <= 0) {
    die("Invalid restaurant.");
}

/* Read form data */
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$rider_id = (int)($_POST['rider_id'] ?? 0);

if ($phone === '' || $address === '') {
    die("Phone number and address are required.");
}

/* Calculate totals */
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_fee = 3.99;
$service_fee = 1.50;
$total_price = $subtotal + $delivery_fee + $service_fee;

/* Platform split logic */
$platform_fee = round($subtotal * 0.10 + $service_fee, 2);
$merchant_amount = round($subtotal * 0.90, 2);
$rider_amount = round($delivery_fee, 2);

$user_id = 1; // temporary
$status = 'pending_payment';
$payment_status = 'pending';

/* Insert order */
$sql_order = "INSERT INTO orders
(user_id, restaurant_id, rider_id, total_price, status, phone, delivery_address, notes,
 payment_status, subtotal, delivery_fee, service_fee, platform_fee, merchant_amount, rider_amount)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_order = $conn->prepare($sql_order);

if (!$stmt_order) {
    die("Prepare failed (orders): " . $conn->error);
}

$stmt_order->bind_param(
    "iiidsssssdddddd",
    $user_id,
    $restaurant_id,
    $rider_id,
    $total_price,
    $status,
    $phone,
    $address,
    $notes,
    $payment_status,
    $subtotal,
    $delivery_fee,
    $service_fee,
    $platform_fee,
    $merchant_amount,
    $rider_amount
);

if (!$stmt_order->execute()) {
    die("Execute failed (orders): " . $stmt_order->error);
}

$order_id = $stmt_order->insert_id;

/* Insert order items */
$sql_item = "INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, price)
             VALUES (?, ?, ?, ?, ?)";
$stmt_item = $conn->prepare($sql_item);

if (!$stmt_item) {
    die("Prepare failed (order_items): " . $conn->error);
}

foreach ($cart as $item) {
    $menu_item_id = $item['id'];
    $item_name = $item['name'];
    $quantity = $item['quantity'];
    $price = $item['price'];

    $stmt_item->bind_param("iisid", $order_id, $menu_item_id, $item_name, $quantity, $price);

    if (!$stmt_item->execute()) {
        die("Execute failed (order_items): " . $stmt_item->error);
    }
}

/* Save for success page if needed */
$_SESSION['last_order_id'] = $order_id;

header("Location: ../Online Payment/create_checkout.php?order_id=" . $order_id);
exit;
?>