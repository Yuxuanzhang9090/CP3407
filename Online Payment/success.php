<?php
session_start();
date_default_timezone_set('Asia/Singapore');
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . "/../Tracking_Order/order_helpers.php");

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
$_SESSION['user_id'] = $order['user_id'];

if (($order['payment_status'] ?? 'pending') !== 'paid') {
    $split_status = 'completed';
    $split_error = null;

    $stmt_update = $conn->prepare("
        UPDATE orders
        SET payment_status = 'paid',
            status = 'paid',
            order_status = 'pending',
            status_updated_at = NOW(),
            split_status = ?,
            split_error = ?
        WHERE id = ?
    ");
    $stmt_update->bind_param("ssi", $split_status, $split_error, $order_id);
    $stmt_update->execute();

    insertOrderStatusHistory($conn, $order_id, 'pending', 'system', 'Payment completed. Order placed successfully.');

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
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #f7f9fc 0%, #eef3f9 100%);
            margin: 0;
            padding: 32px 16px;
            color: #1f2937;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            padding: 38px;
            border-radius: 24px;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
        }

        .hero {
            text-align: center;
            margin-bottom: 28px;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #dcfce7;
            color: #15803d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.15);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 42px;
            line-height: 1.15;
            color: #0f172a;
        }

        .success {
            color: #16a34a;
            font-weight: 700;
            font-size: 20px;
            margin: 0;
        }

        .subtext {
            margin-top: 10px;
            color: #64748b;
            font-size: 15px;
        }

        .section-title {
            margin: 30px 0 14px;
            font-size: 22px;
            color: #0f172a;
            font-weight: 700;
        }

        .info-box {
            margin-top: 14px;
            padding: 22px 24px;
            background: #f8fafc;
            border: 1px solid #e5edf6;
            border-radius: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 28px;
        }

        .info-item {
            min-width: 0;
        }

        .info-label {
            display: block;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .info-value {
            font-size: 18px;
            color: #111827;
            font-weight: 600;
            word-break: break-word;
        }

        .full-span {
            grid-column: 1 / -1;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-dark {
            background: #e2e8f0;
            color: #334155;
        }

        .button-group {
            margin-top: 34px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 54px;
            padding: 14px 18px;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            text-align: center;
            border: 1px solid transparent;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(34, 197, 94, 0.22);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
        }

        .btn-secondary {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
        }

        .btn-secondary:hover {
            background: #1e293b;
        }

        .btn-outline {
            background: #ffffff;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .button-group .btn-wide {
            grid-column: 1 / -1;
        }

        @media (max-width: 720px) {
            .container {
                padding: 24px 18px;
                border-radius: 18px;
            }

            h1 {
                font-size: 32px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                grid-template-columns: 1fr;
            }

            .button-group .btn-wide {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <div class="success-icon">✓</div>
            <h1>Payment Confirmation</h1>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
            <div class="subtext">Your order has been placed and the payment details are shown below.</div>
        </div>

        <div class="section-title">Order Summary</div>
        <div class="info-box">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Order ID</span>
                    <div class="info-value">#<?php echo htmlspecialchars($order_id); ?></div>
                </div>

                <div class="info-item">
                    <span class="info-label">Total Paid</span>
                    <div class="info-value">SGD <?php echo number_format((float)$order['total_price'], 2); ?></div>
                </div>

                <div class="info-item">
                    <span class="info-label">Payment Status</span>
                    <div class="info-value">
                        <span class="badge badge-success"><?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></span>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-label">Order Status</span>
                    <div class="info-value">
                        <span class="badge badge-dark"><?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?></span>
                    </div>
                </div>

                <div class="info-item full-span">
                    <span class="info-label">Stripe Payment ID</span>
                    <div class="info-value"><?php echo htmlspecialchars($stripe_payment_id ?: 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <div class="section-title">Split Payment Result</div>
        <div class="info-box">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Split Status</span>
                    <div class="info-value"><?php echo htmlspecialchars($split_status); ?></div>
                </div>

                <div class="info-item">
                    <span class="info-label">Platform Amount</span>
                    <div class="info-value">SGD <?php echo number_format($platform_amount_display, 2); ?></div>
                </div>

                <div class="info-item">
                    <span class="info-label">Restaurant Amount</span>
                    <div class="info-value">SGD <?php echo number_format($restaurant_amount_display, 2); ?></div>
                </div>

                <div class="info-item">
                    <span class="info-label">Rider Amount</span>
                    <div class="info-value">SGD <?php echo number_format($rider_amount_display, 2); ?></div>
                </div>

                <?php if ($restaurant_transfer_id !== ""): ?>
                    <div class="info-item full-span">
                        <span class="info-label">Restaurant Transfer ID</span>
                        <div class="info-value"><?php echo htmlspecialchars($restaurant_transfer_id); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($rider_transfer_id !== ""): ?>
                    <div class="info-item full-span">
                        <span class="info-label">Rider Transfer ID</span>
                        <div class="info-value"><?php echo htmlspecialchars($rider_transfer_id); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($order['split_error'])): ?>
                    <div class="info-item full-span">
                        <span class="info-label">Split Error</span>
                        <div class="info-value"><?php echo htmlspecialchars($order['split_error']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="button-group">
            <a href="/CP3407/Order_Placing/track_order.php?order_id=<?php echo (int)$order_id; ?>" class="btn btn-primary">
                Track Order
            </a>

            <a href="/CP3407/Browse_Restaurants/categories.php" class="btn btn-secondary">
                Back to Home
            </a>

            <a href="<?php echo htmlspecialchars($stripe_dashboard_all_payments); ?>" target="_blank" class="btn btn-outline">
                View in Stripe Dashboard
            </a>

            <?php if (!empty($stripe_dashboard_this_payment)): ?>
                <a href="<?php echo htmlspecialchars($stripe_dashboard_this_payment); ?>" target="_blank" class="btn btn-outline">
                    View This Payment
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>