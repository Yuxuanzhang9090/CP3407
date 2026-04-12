<?php
session_start();
require_once("../config.php");
require_once("../Order_Placing/order_history_helpers.php");

if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    header("Location: ../Browse_Restaurants/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
}

$sqlOrder = "
    SELECT 
        o.*,
        r.name AS restaurant_name,
        r.address AS restaurant_address,
        rd.name AS rider_name,
        rd.phone AS rider_phone
    FROM orders o
    INNER JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN riders rd ON o.rider_id = rd.id
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
";
$stmtOrder = $conn->prepare($sqlOrder);

if (!$stmtOrder) {
    die("Prepare failed (order): " . $conn->error);
}

$stmtOrder->bind_param("ii", $order_id, $user_id);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();
$order = $orderResult->fetch_assoc();

if (!$order) {
    die("Order not found or access denied.");
}

$sqlItems = "
    SELECT 
        id,
        menu_item_id,
        item_name,
        price,
        quantity
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
";
$stmtItems = $conn->prepare($sqlItems);

if (!$stmtItems) {
    die("Prepare failed (order items): " . $conn->error);
}

$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();

$orderItems = [];
while ($row = $itemsResult->fetch_assoc()) {
    $orderItems[] = $row;
}

$sqlPayment = "
    SELECT *
    FROM payments
    WHERE order_id = ?
    ORDER BY id DESC
    LIMIT 1
";
$stmtPayment = $conn->prepare($sqlPayment);

if (!$stmtPayment) {
    die("Prepare failed (payment): " . $conn->error);
}

$stmtPayment->bind_param("i", $order_id);
$stmtPayment->execute();
$paymentResult = $stmtPayment->get_result();
$payment = $paymentResult->fetch_assoc();

$sqlHistory = "
    SELECT status, notes, updated_by, created_at
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY created_at ASC
";
$stmtHistory = $conn->prepare($sqlHistory);

if (!$stmtHistory) {
    die("Prepare failed (history): " . $conn->error);
}

$stmtHistory->bind_param("i", $order_id);
$stmtHistory->execute();
$historyResult = $stmtHistory->get_result();

$statusHistory = [];
while ($row = $historyResult->fetch_assoc()) {
    $statusHistory[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff8f3;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .top-actions {
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 16px;
            background: #f4c7ab;
            color: #222;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .back-btn:hover {
            background: #efb894;
        }

        h1 {
            margin-bottom: 25px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .card h2 {
            margin-top: 0;
            font-size: 22px;
            color: #222;
        }

        .info-row {
            margin-bottom: 12px;
            line-height: 1.7;
        }

        .badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            margin-right: 8px;
        }

        .status-delivered {
            background: #d8f5dc;
            color: #1b7f35;
        }

        .status-cancelled {
            background: #ffe0e0;
            color: #b42318;
        }

        .status-progress {
            background: #e0edff;
            color: #1d4ed8;
        }

        .status-preparing {
            background: #fff1d6;
            color: #b26a00;
        }

        .status-pending {
            background: #eee;
            color: #555;
        }

        .payment-paid {
            background: #daf4e3;
            color: #177245;
        }

        .payment-failed {
            background: #ffe0e0;
            color: #b42318;
        }

        .payment-refunded {
            background: #ede3ff;
            color: #6b21a8;
        }

        .payment-pending {
            background: #eee;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #fff1e8;
        }

        .total-line {
            text-align: right;
            margin-top: 16px;
            line-height: 1.8;
            font-size: 16px;
        }

        .history-item {
            border-left: 4px solid #ffb58a;
            padding-left: 14px;
            margin-bottom: 16px;
        }

        .history-item strong {
            color: #222;
        }

        .reorder-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 18px;
            background: #ff9f6e;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .reorder-btn:hover {
            background: #f58e58;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-actions">
        <a href="order_history.php" class="back-btn">← Back to Order History</a>
    </div>

    <h1>Order Details - #<?php echo (int)$order['id']; ?></h1>

    <div class="grid">
        <div>
            <div class="card">
                <h2>Order Information</h2>

                <div class="info-row"><strong>Restaurant:</strong> <?php echo htmlspecialchars($order['restaurant_name']); ?></div>
                <div class="info-row"><strong>Restaurant Address:</strong> <?php echo htmlspecialchars($order['restaurant_address'] ?? 'N/A'); ?></div>
                <div class="info-row"><strong>Order Date:</strong> <?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></div>
                <div class="info-row"><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?></div>
                <div class="info-row"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                <div class="info-row"><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes'] ?? 'None'); ?></div>

                <div class="info-row">
                    <strong>Status:</strong>
                    <span class="badge <?php echo getStatusBadgeClass($order['order_status']); ?>">
                        <?php echo htmlspecialchars(formatOrderStatus($order['order_status'])); ?>
                    </span>
                </div>

                <div class="info-row">
                    <strong>Payment Status:</strong>
                    <span class="badge <?php echo getPaymentBadgeClass($order['payment_status']); ?>">
                        <?php echo htmlspecialchars(formatPaymentStatus($order['payment_status'])); ?>
                    </span>
                </div>

                <?php if (!empty($order['rider_name'])): ?>
                    <div class="info-row"><strong>Rider:</strong> <?php echo htmlspecialchars($order['rider_name']); ?></div>
                    <div class="info-row"><strong>Rider Phone:</strong> <?php echo htmlspecialchars($order['rider_phone'] ?? 'N/A'); ?></div>
                <?php endif; ?>

                <a class="reorder-btn"
                   href="reorder.php?order_id=<?php echo (int)$order['id']; ?>"
                   onclick="return confirm('Are you sure ordering this item again?');">
                   One more order
                </a>
            </div>

            <div class="card">
                <h2>Ordered Items</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td>$<?php echo number_format((float)$item['price'], 2); ?></td>
                                <td><?php echo (int)$item['quantity']; ?></td>
                                <td>$<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="total-line">
                    <div><strong>Subtotal:</strong> $<?php echo number_format((float)$order['subtotal'], 2); ?></div>
                    <div><strong>Delivery Fee:</strong> $<?php echo number_format((float)$order['delivery_fee'], 2); ?></div>
                    <div><strong>Service Fee:</strong> $<?php echo number_format((float)$order['service_fee'], 2); ?></div>
                    <div><strong>Total:</strong> $<?php echo number_format((float)$order['total_price'], 2); ?></div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h2>Payment Information</h2>

                <?php if ($payment): ?>
                    <div class="info-row"><strong>Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></div>
                    <div class="info-row"><strong>Amount:</strong> $<?php echo number_format((float)$payment['amount'], 2); ?></div>
                    <div class="info-row"><strong>Status:</strong> <?php echo htmlspecialchars(formatPaymentStatus($payment['payment_status'])); ?></div>
                    <div class="info-row"><strong>Transaction Reference:</strong> <?php echo htmlspecialchars($payment['transaction_reference'] ?? 'N/A'); ?></div>
                    <div class="info-row"><strong>Paid At:</strong> <?php echo !empty($payment['paid_at']) ? date("d M Y, h:i A", strtotime($payment['paid_at'])) : 'N/A'; ?></div>
                <?php else: ?>
                    <p>No payment record found for this order.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Status History</h2>

                <?php if (!empty($statusHistory)): ?>
                    <?php foreach ($statusHistory as $history): ?>
                        <div class="history-item">
                            <div><strong><?php echo htmlspecialchars(formatOrderStatus($history['status'])); ?></strong></div>
                            <div><?php echo htmlspecialchars($history['notes'] ?? ''); ?></div>
                            <div style="color:#666; font-size:14px; margin-top:6px;">
                                Updated by: <?php echo htmlspecialchars($history['updated_by'] ?? 'system'); ?> |
                                <?php echo date("d M Y, h:i A", strtotime($history['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No status history available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>