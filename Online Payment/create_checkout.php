<?php
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../vendor/stripe/stripe-php/init.php");

\Stripe\Stripe::setApiKey($stripe_secret_key);

\Stripe\Stripe::setApiKey($stripe_secret_key);

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die("Invalid order ID.");
}

$stmt = $conn->prepare("
    SELECT o.*, 
           r.stripe_account_id AS restaurant_account_id,
           d.stripe_account_id AS rider_account_id
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

if (empty($order['restaurant_account_id'])) {
    die("Restaurant test account missing.");
}

if (!empty($order['rider_id']) && empty($order['rider_account_id'])) {
    die("Rider test account missing.");
}

$total_price = (float)$order['total_price'];
$merchant_amount = (float)$order['merchant_amount'];
$rider_amount = (float)$order['rider_amount'];
$platform_fee = round($total_price - $merchant_amount - $rider_amount, 2);

if ($platform_fee < 0) {
    die("Split amounts exceed total.");
}

$amount_cents = (int) round($total_price * 100);
$transfer_group = "ORDER_" . $order_id;

try {
    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'locale' => 'en',
        'client_reference_id' => (string)$order_id,
        'line_items' => [[
            'price_data' => [
                'currency' => 'sgd',
                'product_data' => [
                    'name' => 'Food Delivery Order #' . $order_id,
                ],
                'unit_amount' => $amount_cents,
            ],
            'quantity' => 1,
        ]],
        'payment_intent_data' => [
            'transfer_group' => $transfer_group,
        ],
        'metadata' => [
            'order_id' => (string)$order_id,
            'transfer_group' => $transfer_group,
        ],
        'success_url' => 'http://localhost/CP3407/Online%20Payment/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost/CP3407/Online%20Payment/online_payment.php?order_id=' . $order_id,
    ]);

    $stmt2 = $conn->prepare("
        UPDATE orders
        SET stripe_checkout_session_id = ?, platform_fee = ?
        WHERE id = ?
    ");
    $stmt2->bind_param("sdi", $session->id, $platform_fee, $order_id);
    $stmt2->execute();

    header("Location: " . $session->url);
    exit;
} catch (Exception $e) {
    die("Checkout creation failed: " . $e->getMessage());
}
?>