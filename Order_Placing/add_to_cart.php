<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../config.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if ($item_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID.'
    ]);
    exit;
}


$sql = "
    SELECT id, restaurant_id, name, price
    FROM menu_items
    WHERE id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Menu item not found.'
    ]);
    exit;
}

$item = $result->fetch_assoc();

$restaurant_id = (int)$item['restaurant_id'];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$current_restaurant_id = isset($_SESSION['restaurant_id']) ? (int)$_SESSION['restaurant_id'] : 0;

if ($current_restaurant_id > 0 && $current_restaurant_id !== $restaurant_id) {
    $_SESSION['cart'] = [];
}

$_SESSION['restaurant_id'] = $restaurant_id;

if (isset($_SESSION['cart'][$item_id]) && is_array($_SESSION['cart'][$item_id])) {
    $_SESSION['cart'][$item_id]['quantity'] += 1;
} else {
    $_SESSION['cart'][$item_id] = [
        'id' => (int)$item['id'],
        'name' => (string)$item['name'],
        'price' => (float)$item['price'],
        'quantity' => 1
    ];
}


$cart_count = 0;
$cart_total = 0.0;

foreach ($_SESSION['cart'] as $cart_item) {
    if (!is_array($cart_item)) {
        continue;
    }

    $quantity = (int)($cart_item['quantity'] ?? 0);
    $price = (float)($cart_item['price'] ?? 0);

    $cart_count += $quantity;
    $cart_total += ($price * $quantity);
}

echo json_encode([
    'success' => true,
    'message' => 'Item added to cart.',
    'cart_count' => $cart_count,
    'cart_total' => number_format($cart_total, 1, '.', '')
]);
exit;
?>