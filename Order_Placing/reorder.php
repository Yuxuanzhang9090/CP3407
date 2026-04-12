<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    header("Location: ../Browse_Restaurants/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
}


$stmt_order = $conn->prepare("
    SELECT id, restaurant_id
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

if (!$stmt_order) {
    die("Prepare failed (order): " . $conn->error);
}

$stmt_order->bind_param("ii", $order_id, $user_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows === 0) {
    die("Order not found or access denied.");
}

$order = $result_order->fetch_assoc();
$restaurant_id = (int)$order['restaurant_id'];

if ($restaurant_id <= 0) {
    die("Invalid restaurant ID in order.");
}


$stmt_items = $conn->prepare("
    SELECT menu_item_id, item_name, price, quantity
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

if (!$stmt_items) {
    die("Prepare failed (order items): " . $conn->error);
}

$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$order_items = [];
while ($row = $result_items->fetch_assoc()) {
    $order_items[] = $row;
}

if (empty($order_items)) {
    die("No items found in this order.");
}


$_SESSION['cart'] = [];
$_SESSION['restaurant_id'] = $restaurant_id;


foreach ($order_items as $item) {
    $menu_item_id = (int)$item['menu_item_id'];
    $item_name = (string)$item['item_name'];
    $price = (float)$item['price'];
    $quantity = (int)$item['quantity'];

    if ($menu_item_id <= 0 || $quantity <= 0) {
        continue;
    }

    $_SESSION['cart'][$menu_item_id] = [
        'id' => $menu_item_id,
        'name' => $item_name,
        'price' => $price,
        'quantity' => $quantity
    ];
}

header("Location: ../Browse_Restaurants/menu.php?restaurant_id=" . $restaurant_id);
exit;
?>