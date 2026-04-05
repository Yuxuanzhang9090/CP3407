<?php
session_start();
require_once("../config.php");
require_once("../Tracking_Order/order_helpers.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Please log in first']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$status_labels = getOrderStatusLabels();

$stmt = $conn->prepare("
    SELECT o.order_status, o.status_updated_at, o.estimated_delivery_time,
           d.name AS rider_name, d.phone AS rider_phone, d.vehicle AS rider_vehicle
    FROM orders o
    LEFT JOIN riders d ON o.rider_id = d.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$stmtHistory = $conn->prepare("
    SELECT status, notes, created_at
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY created_at ASC
");
$stmtHistory->bind_param("i", $order_id);
$stmtHistory->execute();
$history = $stmtHistory->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtHistory->close();

$stmtLocation = $conn->prepare("
    SELECT latitude, longitude, created_at
    FROM order_tracking
    WHERE order_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmtLocation->bind_param("i", $order_id);
$stmtLocation->execute();
$location = $stmtLocation->get_result()->fetch_assoc();
$stmtLocation->close();

echo json_encode([
    'status' => $order['order_status'],
    'status_text' => $status_labels[$order['order_status']] ?? $order['order_status'],
    'status_updated_at' => $order['status_updated_at'],
    'estimated_delivery_time' => $order['estimated_delivery_time'],
    'rider' => [
        'name' => $order['rider_name'],
        'phone' => $order['rider_phone'],
        'vehicle' => $order['rider_vehicle']
    ],
    'location' => $location ?: null,
    'history' => $history
]);