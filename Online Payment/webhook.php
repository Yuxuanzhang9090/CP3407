<?php
require_once("config.php");
require_once("vendor/autoload.php");

\Stripe\Stripe::setApiKey($stripe_secret_key);

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $stripe_webhook_secret
    );
} catch (Exception $e) {
    http_response_code(400);
    exit('Webhook Error');
}

if ($event->type !== 'checkout.session.completed') {
    http_response_code(200);
    exit('Ignored');
}

$session = $event->data->object;
$order_id = (int)($session->client_reference_id ?? 0);

if ($order_id <= 0) {
    http_response_code(200);
    exit('Invalid order');
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
    http_response_code(200);
    exit('Order not found');
}

$order = $result->fetch_assoc();

if (($order['split_status'] ?? '') === 'completed') {
    http_response_code(200);
    exit('Already processed');
}

$payment_intent_id = $session->payment_intent ?? null;
if (!$payment_intent_id) {
    http_response_code(200);
    exit('No payment intent');
}

try {
    $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    $charge_id = $paymentIntent->latest_charge ?? null;
    $transfer_group = $paymentIntent->transfer_group ?? ("ORDER_" . $order_id);

    if (!$charge_id) {
        throw new Exception("Charge not found.");
    }

    $stmt_update = $conn->prepare("
        UPDATE orders
        SET payment_status = 'paid',
            status = 'paid',
            stripe_payment_intent_id = ?,
            is_paid = 1
        WHERE id = ?
    ");
    $stmt_update->bind_param("si", $payment_intent_id, $order_id);
    $stmt_update->execute();

    $restaurant_ok = false;
    $rider_ok = false;
    $errors = [];

    /* Restaurant transfer */
    if ((float)$order['merchant_amount'] > 0 && !empty($order['restaurant_account_id'])) {
        $merchant_cents = (int) round($order['merchant_amount'] * 100);

        $transfer1 = \Stripe\Transfer::create([
            'amount' => $merchant_cents,
            'currency' => 'sgd',
            'destination' => $order['restaurant_account_id'],
            'source_transaction' => $charge_id,
            'transfer_group' => $transfer_group,
            'metadata' => [
                'order_id' => (string)$order_id,
                'recipient_type' => 'restaurant'
            ]
        ]);

        $stmt_t1 = $conn->prepare("
            INSERT INTO transfers (order_id, recipient_type, recipient_account_id, amount, stripe_transfer_id, status)
            VALUES (?, 'restaurant', ?, ?, ?, 'paid')
        ");
        $transfer_id1 = $transfer1->id;
        $amount1 = $order['merchant_amount'];
        $stmt_t1->bind_param("isds", $order_id, $order['restaurant_account_id'], $amount1, $transfer_id1);
        $stmt_t1->execute();

        $restaurant_ok = true;
    } else {
        $errors[] = "Restaurant transfer failed";
    }

    /* Rider transfer */
    if ((float)$order['rider_amount'] > 0 && !empty($order['rider_account_id'])) {
        $rider_cents = (int) round($order['rider_amount'] * 100);

        $transfer2 = \Stripe\Transfer::create([
            'amount' => $rider_cents,
            'currency' => 'sgd',
            'destination' => $order['rider_account_id'],
            'source_transaction' => $charge_id,
            'transfer_group' => $transfer_group,
            'metadata' => [
                'order_id' => (string)$order_id,
                'recipient_type' => 'rider'
            ]
        ]);

        $stmt_t2 = $conn->prepare("
            INSERT INTO transfers (order_id, recipient_type, recipient_account_id, amount, stripe_transfer_id, status)
            VALUES (?, 'rider', ?, ?, ?, 'paid')
        ");
        $transfer_id2 = $transfer2->id;
        $amount2 = $order['rider_amount'];
        $stmt_t2->bind_param("isds", $order_id, $order['rider_account_id'], $amount2, $transfer_id2);
        $stmt_t2->execute();

        $rider_ok = true;
    } elseif ((float)$order['rider_amount'] <= 0) {
        $rider_ok = true;
    } else {
        $errors[] = "Rider transfer failed";
    }

    if ($restaurant_ok && $rider_ok) {
        $split_status = 'completed';
        $split_error = null;
    } elseif ($restaurant_ok || $rider_ok) {
        $split_status = 'partial_failed';
        $split_error = implode(" | ", $errors);
    } else {
        $split_status = 'failed';
        $split_error = implode(" | ", $errors);
    }

    $stmt_done = $conn->prepare("
        UPDATE orders
        SET split_status = ?, split_error = ?
        WHERE id = ?
    ");
    $stmt_done->bind_param("ssi", $split_status, $split_error, $order_id);
    $stmt_done->execute();

} catch (Exception $e) {
    $stmt_fail = $conn->prepare("
        UPDATE orders
        SET split_status = 'failed', split_error = ?
        WHERE id = ?
    ");
    $err = $e->getMessage();
    $stmt_fail->bind_param("si", $err, $order_id);
    $stmt_fail->execute();
}

http_response_code(200);
echo "OK";
?>