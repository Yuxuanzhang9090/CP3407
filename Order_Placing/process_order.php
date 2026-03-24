<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* Read cart and restaurant from session */
$cart = $_SESSION['cart'] ?? [];
$restaurant_id = $_SESSION['restaurant_id'] ?? 0;

/* Check cart */
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
$rider_name = trim($_POST['rider_name'] ?? '');
$rider_phone = trim($_POST['rider_phone'] ?? '');

/* Basic validation */
if ($phone === '' || $address === '') {
    die("Phone number and address are required.");
}

/* Calculate total price */
$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

/*
   Insert into orders
   IMPORTANT:
   Your current orders table has:
   id, user_id, restaurant_id, total_price, status, created_at

   If user login is not ready, temporarily use user_id = 1
*/
$user_id = 1;
$status = 'pending';

$sql_order = "INSERT INTO orders (user_id, restaurant_id, total_price, status)
              VALUES (?, ?, ?, ?)";
$stmt_order = $conn->prepare($sql_order);

if (!$stmt_order) {
    die("Prepare failed (orders): " . $conn->error);
}

$stmt_order->bind_param("iids", $user_id, $restaurant_id, $total_price, $status);

if (!$stmt_order->execute()) {
    die("Execute failed (orders): " . $stmt_order->error);
}

$order_id = $stmt_order->insert_id;

/* Insert each cart item into order_items
   Current table: id, order_id, menu_item_id, item_name, quantity, price
*/
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

/* Save extra order info into session for later payment/success pages */
$_SESSION['last_order'] = [
    'order_id' => $order_id,
    'phone' => $phone,
    'address' => $address,
    'notes' => $notes,
    'rider_name' => $rider_name,
    'rider_phone' => $rider_phone,
    'total_price' => $total_price
];

/* Clear cart after successful order creation */
unset($_SESSION['cart']);

/* Redirect to payment page */
header("Location: payment.php?order_id=" . $order_id);
exit;
?>