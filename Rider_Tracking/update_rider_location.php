<?php
session_start();
require_once("../config.php");

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$rider_id = isset($data['rider_id']) ? (int)$data['rider_id'] : 0;
$latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;

if ($order_id <= 0 || $rider_id <= 0 || $latitude === null || $longitude === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing or invalid parameters.'
    ]);
    exit;
}

$stmtCheck = $conn->prepare("
    SELECT id
    FROM orders
    WHERE id = ? AND rider_id = ?
    LIMIT 1
");
$stmtCheck->bind_param("ii", $order_id, $rider_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Order and rider do not match.'
    ]);
    exit;
}
$stmtCheck->close();

/*
| Insert latest rider location
*/
$stmt = $conn->prepare("
    INSERT INTO order_tracking (order_id, rider_id, latitude, longitude, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("iidd", $order_id, $rider_id, $latitude, $longitude);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Database insert failed.'
    ]);
}

$stmt->close();