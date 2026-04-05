<?php
session_start();
date_default_timezone_set('Asia/Singapore');
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/order_helpers.php");

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

$stmt = $conn->prepare("
    SELECT id, user_id, order_status, status_updated_at
    FROM orders
    WHERE id = ? AND user_id = ?
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

$current_status = $order['order_status'] ?? 'pending';
$status_updated_at = $order['status_updated_at'] ?? null;

$flow = [
    'pending' => 'confirmed',
    'confirmed' => 'preparing',
    'preparing' => 'ready_for_pickup',
    'ready_for_pickup' => 'picked_up',
    'picked_up' => 'on_the_way',
    'on_the_way' => 'delivered'
];

$notes_map = [
    'confirmed' => 'Restaurant accepted your order.',
    'preparing' => 'The kitchen is preparing your food.',
    'ready_for_pickup' => 'Order is packed and ready for pickup.',
    'picked_up' => 'Rider has picked up the order.',
    'on_the_way' => 'Rider is on the way to your location.',
    'delivered' => 'Order delivered successfully.'
];

$interval_seconds = 10;

if ($current_status === 'delivered' || $current_status === 'cancelled') {
    echo json_encode([
        'success' => true,
        'message' => 'Order already completed.',
        'current_status' => $current_status
    ]);
    exit;
}

if (empty($status_updated_at)) {
    $status_updated_at = date('Y-m-d H:i:s');
}

$last_time = strtotime($status_updated_at);
$now_time = time();
$diff = $now_time - $last_time;

if ($diff < 0) {
    $diff = 999999;
}

if ($diff >= $interval_seconds && isset($flow[$current_status])) {
    $next_status = $flow[$current_status];
    $notes = $notes_map[$next_status] ?? '';

    updateOrderStatus($conn, $order_id, $next_status, 'system', $notes);

    $stmtRider = $conn->prepare("
        SELECT rider_id
        FROM orders
        WHERE id = ?
    ");
    $stmtRider->bind_param("i", $order_id);
    $stmtRider->execute();
    $riderResult = $stmtRider->get_result()->fetch_assoc();
    $stmtRider->close();

    $rider_id = (int)($riderResult['rider_id'] ?? 0);

    if ($rider_id > 0 && ($next_status === 'picked_up' || $next_status === 'on_the_way')) {
        $demo_locations = [
            'picked_up' => [1.3142000, 103.8632000],
            'on_the_way' => [1.3168000, 103.8659000]
        ];

        if (isset($demo_locations[$next_status])) {
            $lat = $demo_locations[$next_status][0];
            $lng = $demo_locations[$next_status][1];
            insertRiderLocation($conn, $order_id, $rider_id, $lat, $lng);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status updated automatically.',
        'previous_status' => $current_status,
        'current_status' => $next_status
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'No update needed yet.',
    'current_status' => $current_status,
    'seconds_since_last_update' => $diff
]);