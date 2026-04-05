<?php
date_default_timezone_set('Asia/Singapore');
function getOrderStatusLabels() {
    return [
        'pending' => 'Order Placed',
        'confirmed' => 'Confirmed by Restaurant',
        'preparing' => 'Preparing Your Food',
        'ready_for_pickup' => 'Ready for Pickup',
        'picked_up' => 'Picked Up by Rider',
        'on_the_way' => 'On the Way',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
}

function getTrackingSteps() {
    return [
        'pending',
        'confirmed',
        'preparing',
        'ready_for_pickup',
        'picked_up',
        'on_the_way',
        'delivered'
    ];
}

function getStepIndex($status) {
    $steps = getTrackingSteps();
    $index = array_search($status, $steps);
    return ($index === false) ? -1 : $index;
}

function insertOrderStatusHistory($conn, $order_id, $status, $updated_by = 'system', $notes = '') {
    $stmt = $conn->prepare("
        INSERT INTO order_status_history (order_id, status, updated_by, notes, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $order_id, $status, $updated_by, $notes);
    $stmt->execute();
    $stmt->close();
}

function assignAvailableRider($conn) {
    $sql = "SELECT id FROM riders WHERE status = 'available' ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['id'];
    }

    return null;
}

function updateOrderStatus($conn, $order_id, $new_status, $updated_by = 'system', $notes = '') {
    $status_updated_at = date('Y-m-d H:i:s');

    if ($new_status === 'delivered') {
        $stmt = $conn->prepare("
            UPDATE orders
            SET order_status = ?, status_updated_at = ?, delivered_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $new_status, $status_updated_at, $status_updated_at, $order_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE orders
            SET order_status = ?, status_updated_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $new_status, $status_updated_at, $order_id);
    }

    $stmt->execute();
    $stmt->close();

    insertOrderStatusHistory($conn, $order_id, $new_status, $updated_by, $notes);

    if ($new_status === 'picked_up' || $new_status === 'on_the_way') {
        $stmtRider = $conn->prepare("
            UPDATE riders r
            JOIN orders o ON r.id = o.rider_id
            SET r.status = 'busy'
            WHERE o.id = ?
        ");
        $stmtRider->bind_param("i", $order_id);
        $stmtRider->execute();
        $stmtRider->close();
    }

    if ($new_status === 'delivered' || $new_status === 'cancelled') {
        $stmtRider = $conn->prepare("
            UPDATE riders r
            JOIN orders o ON r.id = o.rider_id
            SET r.status = 'available'
            WHERE o.id = ?
        ");
        $stmtRider->bind_param("i", $order_id);
        $stmtRider->execute();
        $stmtRider->close();
    }
}

function insertRiderLocation($conn, $order_id, $rider_id, $latitude, $longitude) {
    $stmt = $conn->prepare("
        INSERT INTO order_tracking (order_id, rider_id, latitude, longitude, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iidd", $order_id, $rider_id, $latitude, $longitude);
    $stmt->execute();
    $stmt->close();
}