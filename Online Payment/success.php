<?php
session_start();
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../vendor/autoload.php");

\Stripe\Stripe::setApiKey($stripe_secret_key);

$session_id = $_GET['session_id'] ?? '';
if ($session_id === '') {
    die("Missing session ID.");
}

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
} catch (Exception $e) {
    die("Cannot retrieve session: " . $e->getMessage());
}

$order_id = (int)($session->client_reference_id ?? 0);
if ($order_id <= 0) {
    die("Invalid order.");
}

$stripe_payment_id = $session->payment_intent ?? '';

$stmt = $conn->prepare("
    SELECT o.*, r.name AS restaurant_name, d.name AS rider_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN riders d ON o.rider_id = d.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();

/* 模拟支付成功 + 模拟分账 */
if (($order['payment_status'] ?? 'pending') !== 'paid') {
    $split_status = 'completed';
    $split_error = null;

    $stmt_update = $conn->prepare("
        UPDATE orders
        SET payment_status = 'paid',
            status = 'paid',
            split_status = ?,
            split_error = ?
        WHERE id = ?
    ");
    $stmt_update->bind_param("ssi", $split_status, $split_error, $order_id);
    $stmt_update->execute();

    $check = $conn->prepare("
        SELECT id
        FROM transfers
        WHERE order_id = ?
    ");
    $check->bind_param("i", $order_id);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows === 0) {
        if ((float)$order['merchant_amount'] > 0) {
            $stmt_t1 = $conn->prepare("
                INSERT INTO transfers
                (order_id, recipient_type, recipient_account_id, amount, stripe_transfer_id, status)
                VALUES (?, 'restaurant', 'SIM_RESTAURANT', ?, ?, 'paid')
            ");
            $fake_restaurant_transfer_id = "SIM_REST_" . $order_id;
            $merchant_amount = (float)$order['merchant_amount'];
            $stmt_t1->bind_param("ids", $order_id, $merchant_amount, $fake_restaurant_transfer_id);
            $stmt_t1->execute();
        }

        if ((float)$order['rider_amount'] > 0) {
            $stmt_t2 = $conn->prepare("
                INSERT INTO transfers
                (order_id, recipient_type, recipient_account_id, amount, stripe_transfer_id, status)
                VALUES (?, 'rider', 'SIM_RIDER', ?, ?, 'paid')
            ");
            $fake_rider_transfer_id = "SIM_RIDER_" . $order_id;
            $rider_amount = (float)$order['rider_amount'];
            $stmt_t2->bind_param("ids", $order_id, $rider_amount, $fake_rider_transfer_id);
            $stmt_t2->execute();
        }
    }

    $stmt_refresh = $conn->prepare("
        SELECT o.*, r.name AS restaurant_name, d.name AS rider_name
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        LEFT JOIN riders d ON o.rider_id = d.id
        WHERE o.id = ?
    ");
    $stmt_refresh->bind_param("i", $order_id);
    $stmt_refresh->execute();
    $order = $stmt_refresh->get_result()->fetch_assoc();
}
unset($_SESSION['cart']);
unset($_SESSION['restaurant_id']);
$stmt2 = $conn->prepare("
    SELECT recipient_type, amount, stripe_transfer_id, status
    FROM transfers
    WHERE order_id = ?
    ORDER BY id ASC
");
$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$transfer_result = $stmt2->get_result();

$restaurant_transfer_id = "";
$rider_transfer_id = "";

while ($row = $transfer_result->fetch_assoc()) {
    if ($row['recipient_type'] === 'restaurant') {
        $restaurant_transfer_id = $row['stripe_transfer_id'];
    }
    if ($row['recipient_type'] === 'rider') {
        $rider_transfer_id = $row['stripe_transfer_id'];
    }
}

$message = "Your payment was successful.";
$split_status = $order['split_status'] ?? 'pending';
$restaurant_amount_display = (float)($order['merchant_amount'] ?? 0);
$rider_amount_display = (float)($order['rider_amount'] ?? 0);
$platform_amount_display = (float)($order['platform_fee'] ?? 0);

$stripe_dashboard_all_payments = "https://dashboard.stripe.com/test/payments";
$stripe_dashboard_this_payment = !empty($stripe_payment_id)
    ? "https://dashboard.stripe.com/test/payments/" . urlencode($stripe_payment_id)
    : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fb;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 720px;
            margin: 80px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        h1 {
            color: #1f2937;
            margin-bottom: 20px;
        }

        p {
            color: #4b5563;
            font-size: 16px;
            line-height: 1.7;
            margin: 10px 0;
        }

        .success {
            color: #16a34a;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .section-title {
            margin-top: 30px;
            font-size: 20px;
            color: #111827;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            margin-top: 18px;
            margin-right: 10px;
            padding: 12px 24px;
            background: #111827;
            color: white;
            text-decoration: none;
            border-radius: 10px;
        }

        .btn:hover {
            background: #374151;
        }

        .info-box {
            margin-top: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            text-align: left;
        }

        .info-box p {
            margin: 8px 0;
        }

        .button-group {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Confirmation</h1>

        <p class="success"><?php echo htmlspecialchars($message); ?></p>

        <div class="info-box">
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
            <p><strong>Stripe Payment ID:</strong> <?php echo htmlspecialchars($stripe_payment_id ?: 'N/A'); ?></p>

            <?php if ($order): ?>
                <p><strong>Total Paid:</strong> SGD <?php echo number_format((float)$order['total_price'], 2); ?></p>
                <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></p>
            <?php endif; ?>
        </div>

        <div class="section-title">Split Payment Result</div>

        <div class="info-box">
            <p><strong>Split Status:</strong> <?php echo htmlspecialchars($split_status); ?></p>
            <p><strong>Restaurant Amount:</strong> SGD <?php echo number_format($restaurant_amount_display, 2); ?></p>
            <p><strong>Rider Amount:</strong> SGD <?php echo number_format($rider_amount_display, 2); ?></p>
            <p><strong>Platform Amount:</strong> SGD <?php echo number_format($platform_amount_display, 2); ?></p>

            <?php if ($restaurant_transfer_id !== ""): ?>
                <p><strong>Restaurant Transfer ID:</strong> <?php echo htmlspecialchars($restaurant_transfer_id); ?></p>
            <?php endif; ?>

            <?php if ($rider_transfer_id !== ""): ?>
                <p><strong>Rider Transfer ID:</strong> <?php echo htmlspecialchars($rider_transfer_id); ?></p>
            <?php endif; ?>

            <?php if (!empty($order['split_error'])): ?>
                <p><strong>Split Error:</strong> <?php echo htmlspecialchars($order['split_error']); ?></p>
            <?php endif; ?>
        </div>

        <div class="button-group">
            <a href="/CP3407/Browse_Restaurants/categories.php" class="btn">Back to Home</a>

            <a href="<?php echo htmlspecialchars($stripe_dashboard_all_payments); ?>" 
               target="_blank" 
               class="btn">
               View in Stripe Dashboard
            </a>

            <?php if (!empty($stripe_dashboard_this_payment)): ?>
                <a href="<?php echo htmlspecialchars($stripe_dashboard_this_payment); ?>" 
                   target="_blank" 
                   class="btn">
                   View This Payment
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>