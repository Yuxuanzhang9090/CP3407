<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

if (!isset($_POST['item_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Item ID missing"
    ]);
    exit;
}

$item_id = (int)$_POST['item_id'];

$sql = "SELECT id, name, price FROM menu_items WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "SQL prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $item_id);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "SQL execute failed: " . $stmt->error
    ]);
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Menu item not found"
    ]);
    exit;
}

$item = $result->fetch_assoc();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$item_id])) {
    $_SESSION['cart'][$item_id]['quantity'] += 1;
} else {
    $_SESSION['cart'][$item_id] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'price' => (float)$item['price'],
        'quantity' => 1
    ];
}

$cart_count = 0;
$cart_total = 0;

foreach ($_SESSION['cart'] as $cart_item) {
    $cart_count += $cart_item['quantity'];
    $cart_total += $cart_item['price'] * $cart_item['quantity'];
}

echo json_encode([
    "success" => true,
    "cart_count" => $cart_count,
    "cart_total" => number_format($cart_total, 1, '.', '')
]);
exit;
?>