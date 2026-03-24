<?php
require_once("config.php");

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die("Invalid order ID.");
}


$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();

$stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>NomNom - Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CP3407/registration & login/style.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Complete Payment</h2>

    <div class="card p-4">
        <h4>Order Summary</h4>

        <?php while ($item = $result_items->fetch_assoc()): ?>
            <div class="d-flex justify-content-between">
                <span><?php echo htmlspecialchars($item['item_name']); ?> x<?php echo (int)$item['quantity']; ?></span>
                <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            </div>
        <?php endwhile; ?>

        <hr>

        <div class="d-flex justify-content-between">
            <span>Subtotal</span>
            <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Delivery Fee</span>
            <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Service Fee</span>
            <span>$<?php echo number_format($order['service_fee'], 2); ?></span>
        </div>

        <hr>

        <div class="d-flex justify-content-between fw-bold">
            <span>Total</span>
            <span>$<?php echo number_format($order['total_price'], 2); ?></span>
        </div>

        <a href="create_checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary w-100 mt-4">
            Pay with Stripe
        </a>
    </div>
</div>
</body>
</html>