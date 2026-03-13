<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    die("Cart is empty.");
}

$restaurant_id = $_SESSION['restaurant_id'] ?? null;

if (!$restaurant_id) {
    die("Restaurant not found.");
}

$user_id = $_SESSION['user_id'] ?? null;

/* 计算总价 */
$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

/* 插入 orders */
$sql = "INSERT INTO orders (user_id, restaurant_id, total_price, status)
        VALUES (?, ?, ?, 'Pending')";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed (orders): " . $conn->error);
}

if (!$stmt->bind_param("iid", $user_id, $restaurant_id, $total_price)) {
    die("Bind failed (orders): " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Execute failed (orders): " . $stmt->error);
}

$order_id = $stmt->insert_id;

/* 插入 order_items */
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

    if (!$stmt_item->bind_param("iisid", $order_id, $menu_item_id, $item_name, $quantity, $price)) {
        die("Bind failed (order_items): " . $stmt_item->error);
    }

    if (!$stmt_item->execute()) {
        die("Execute failed (order_items): " . $stmt_item->error);
    }
}

/* 写入成功后清空购物车 */
unset($_SESSION['cart']);

echo "Order placed successfully! Order ID: " . $order_id;
?>