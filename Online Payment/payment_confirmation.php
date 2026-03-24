<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("config.php");
require_once("vendor/autoload.php");

\Stripe\Stripe::setApiKey($stripe_secret_key);

$session_id = $_GET['session_id'] ?? '';

if ($session_id === '') {
    die("Missing session ID.");
}

$message = "";
$order_id = 0;
$order = null;

$split_status = "Not processed";
$restaurant_transfer_id = "";
$rider_transfer_id = "";

$restaurant_amount_display = 0;
$rider_amount_display = 0;
$platform_amount_display = 0;

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    $order_id = (int)($session->client_reference_id ?? 0);

    if ($order_id <= 0) {
        die("Invalid order ID.");
    }

    // Check the order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        die("Order not found.");
    }

    // If payment is successful and order not processed yet
    if ($session->payment_status === 'paid' && $order['is_paid'] == 0) {

        $total = (int) round($order['total_price'] * 100);

        // Split logic
        $restaurant_amount = (int) round($total * 0.6);
        $rider_amount = (int) round($total * 0.3);
        $platform_amount = $total - $restaurant_amount - $rider_amount;

        // For display
        $restaurant_amount_display = $restaurant_amount / 100;
        $rider_amount_display = $rider_amount / 100;
        $platform_amount_display = $platform_amount / 100;

        // Check restaurant Stripe account
        $stmt = $conn->prepare("SELECT stripe_account_id FROM restaurants WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $order['restaurant_id']);
        $stmt->execute();
        $restaurant = $stmt->get_result()->fetch_assoc();

        // Check rider Stripe account
        $stmt = $conn->prepare("SELECT stripe_account_id FROM riders WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $order['rider_id']);
        $stmt->execute();
        $rider = $stmt->get_result()->fetch_assoc();

        // Default split message
        $split_status = "Split payment not completed because Stripe account ID is missing.";

        // Only transfer if both Stripe account IDs exist
        if (!empty($restaurant['stripe_account_id']) && !empty($rider['stripe_account_id'])) {

            // Transfer to restaurant
            $transfer1 = \Stripe\Transfer::create([
                "amount" => $restaurant_amount,
                "currency" => "sgd",
                "destination" => $restaurant['stripe_account_id'],
            ]);

            // Transfer to rider
            $transfer2 = \Stripe\Transfer::create([
                "amount" => $rider_amount,
                "currency" => "sgd",
                "destination" => $rider['stripe_account_id'],
            ]);

            $restaurant_transfer_id = $transfer1->id;
            $rider_transfer_id = $transfer2->id;
            $split_status = "Split payment completed successfully.";

            // Insert restaurant transfer record
            $stmt = $conn->prepare("
                INSERT INTO transfers (
                    order_id,
                    recipient_type,
                    recipient_account_id,
                    amount,
                    stripe_transfer_id,
                    status
                ) VALUES (?, 'restaurant', ?, ?, ?, 'completed')
            ");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $restaurant_amount_decimal = $restaurant_amount / 100;
            $stmt->bind_param(
                "isds",
                $order_id,
                $restaurant['stripe_account_id'],
                $restaurant_amount_decimal,
                $restaurant_transfer_id
            );
            $stmt->execute();

            // Insert rider transfer record
            $stmt = $conn->prepare("
                INSERT INTO transfers (
                    order_id,
                    recipient_type,
                    recipient_account_id,
                    amount,
                    stripe_transfer_id,
                    status
                ) VALUES (?, 'rider', ?, ?, ?, 'completed')
            ");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $rider_amount_decimal = $rider_amount / 100;
            $stmt->bind_param(
                "isds",
                $order_id,
                $rider['stripe_account_id'],
                $rider_amount_decimal,
                $rider_transfer_id
            );
            $stmt->execute();
        }

        // Update order
        $stmt = $conn->prepare("
            UPDATE orders
            SET
                payment_status = 'paid',
                is_paid = 1,
                platform_fee = ?,
                merchant_amount = ?,
                rider_amount = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $platform_fee_decimal = $platform_amount / 100;
        $merchant_amount_decimal = $restaurant_amount / 100;
        $rider_amount_decimal = $rider_amount / 100;

        $stmt->bind_param(
            "dddi",
            $platform_fee_decimal,
            $merchant_amount_decimal,
            $rider_amount_decimal,
            $order_id
        );
        $stmt->execute();

        // Refresh order data after update
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        // Clear session cart
        unset($_SESSION['cart']);
        unset($_SESSION['restaurant_id']);

        $message = "Payment successful and order has been processed.";
    }

    // If already paid before
    elseif ($session->payment_status === 'paid' && $order['is_paid'] == 1) {

        $message = "Payment successful. This order was already processed earlier.";

        // Show saved amounts from database
        $restaurant_amount_display = (float)($order['merchant_amount'] ?? 0);
        $rider_amount_display = (float)($order['rider_amount'] ?? 0);
        $platform_amount_display = (float)($order['platform_fee'] ?? 0);

        // Read existing transfer info from database
        $stmt = $conn->prepare("
            SELECT recipient_type, stripe_transfer_id
            FROM transfers
            WHERE order_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $transfer_result = $stmt->get_result();

            $found_transfer = false;

            while ($row = $transfer_result->fetch_assoc()) {
                $found_transfer = true;

                if ($row['recipient_type'] === 'restaurant') {
                    $restaurant_transfer_id = $row['stripe_transfer_id'];
                }

                if ($row['recipient_type'] === 'rider') {
                    $rider_transfer_id = $row['stripe_transfer_id'];
                }
            }

            if ($found_transfer) {
                $split_status = "Split payment had already been completed earlier.";
            } else {
                $split_status = "Payment completed, but no transfer records were found.";
            }
        }
    }

    // If payment is not completed
    else {
        $message = "Payment not completed yet.";
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
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