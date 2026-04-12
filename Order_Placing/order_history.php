<?php
session_start();
require_once("../config.php");
require_once("../Order_Placing/order_history_helpers.php");

if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    header("Location: ../registration%20&%20login/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
    SELECT 
        o.id,
        o.total_price,
        o.order_status,
        o.payment_status,
        o.created_at,
        o.restaurant_id,
        r.name AS restaurant_name,
        COUNT(oi.id) AS total_items
    FROM orders o
    INNER JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id, o.total_price, o.order_status, o.payment_status, o.created_at, o.restaurant_id, r.name
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
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

        h1 {
            margin-bottom: 25px;
            color: #222;
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

        .empty-box {
            background: #fff;
            border-radius: 14px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        }

        .empty-box h2 {
            margin-top: 0;
            color: #444;
        }

        .empty-box p {
            color: #666;
        }

        .browse-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 18px;
            background: #ff9f6e;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .browse-btn:hover {
            background: #f58e58;
        }

        .orders-grid {
            display: grid;
            gap: 18px;
        }

        .order-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .order-left h3 {
            margin: 0 0 8px;
            color: #222;
        }

        .order-meta {
            color: #666;
            line-height: 1.7;
            font-size: 14px;
        }

        .order-right {
            text-align: right;
        }

        .price {
            font-size: 22px;
            font-weight: bold;
            color: #222;
            margin-bottom: 10px;
        }

        .badges {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
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

        .order-actions {
            margin-top: 18px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn-details {
            background: #ffb58a;
            color: #fff;
        }

        .btn-details:hover {
            background: #f59c6a;
        }

        .btn-reorder {
            background: #f4c7ab;
            color: #222;
        }

        .btn-reorder:hover {
            background: #ebb48f;
        }

        @media (max-width: 768px) {
            .order-right {
                text-align: left;
            }

            .badges {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-actions">
        <a href="../Browse_Restaurants/categories.php" class="back-btn">← Back to Restaurants</a>
    </div>

    <h1>Your Order History</h1>

    <?php if (empty($orders)): ?>
        <div class="empty-box">
            <h2>No orders yet</h2>
            <p>You have not placed any orders yet. Start exploring restaurants and place your first order.</p>
            <a href="../Browse_Restaurants/categories.php" class="browse-btn">Browse Restaurants</a>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-top">
                        <div class="order-left">
                            <h3>Order #<?php echo (int)$order['id']; ?> - <?php echo htmlspecialchars($order['restaurant_name']); ?></h3>
                            <div class="order-meta">
                                <div><strong>Date:</strong> <?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></div>
                                <div><strong>Items:</strong> <?php echo (int)$order['total_items']; ?></div>
                                <div><strong>Restaurant ID:</strong> <?php echo (int)$order['restaurant_id']; ?></div>
                            </div>
                        </div>

                        <div class="order-right">
                            <div class="price">$<?php echo number_format((float)$order['total_price'], 2); ?></div>
                            <div class="badges">
                                <span class="badge <?php echo getStatusBadgeClass($order['order_status']); ?>">
                                    <?php echo htmlspecialchars(formatOrderStatus($order['order_status'])); ?>
                                </span>
                                <span class="badge <?php echo getPaymentBadgeClass($order['payment_status']); ?>">
                                    Payment: <?php echo htmlspecialchars(formatPaymentStatus($order['payment_status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <a class="btn btn-details" href="order_details.php?order_id=<?php echo (int)$order['id']; ?>">
                            View Details
                        </a>
                        <a class="btn btn-reorder" href="reorder.php?order_id=<?php echo (int)$order['id']; ?>"
                           onclick="return confirm('Do you want to reorder these items? This will replace your current cart if it belongs to another restaurant.');">
                            Reorder
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>