<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* Read cart and restaurant from session */
$cart = $_SESSION['cart'] ?? [];
$restaurant_id = $_SESSION['restaurant_id'] ?? 0;

/* Check cart */
if (empty($cart)) {
    die("Your cart is empty.");
}

if ($restaurant_id <= 0) {
    die("Invalid restaurant.");
}

/* Get restaurant details */
$sql_restaurant = "SELECT * FROM restaurants WHERE id = ?";
$stmt = $conn->prepare($sql_restaurant);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result_restaurant = $stmt->get_result();

if ($result_restaurant->num_rows === 0) {
    die("Restaurant not found.");
}

$restaurant = $result_restaurant->fetch_assoc();

/* Calculate total */
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_fee = 3.99;
$service_fee = 1.50;
$total_price = $subtotal + $delivery_fee + $service_fee;

/* Random rider info from database */
$sql_rider = "SELECT * FROM riders WHERE status = 'available' ORDER BY RAND() LIMIT 1";
$result_rider = $conn->query($sql_rider);

if ($result_rider && $result_rider->num_rows > 0) {
    $rider = $result_rider->fetch_assoc();

    $rider_id = $rider['id'];
    $rider_name = $rider['name'];
    $rider_phone = $rider['phone'];
    $rider_vehicle = $rider['vehicle'];
    $rider_eta = "25 mins";
} else {
    $rider_id = 0;
    $rider_name = "No rider available";
    $rider_phone = "-";
    $rider_vehicle = "-";
    $rider_eta = "-";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CP3407/registration & login/style.css">
</head>
<body class="checkout-body">

<div class="checkout-wrapper">
    <div class="checkout-header mb-4">
        <div>
            <span class="checkout-badge">Final Step</span>
            <h1 class="checkout-title">Confirm Your Order</h1>
            <p class="checkout-subtitle">Review your items, delivery details, and rider information before placing the order.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT SIDE -->
        <div class="col-lg-8">
            <!-- Restaurant Info -->
            <div class="checkout-card mb-4">
                <div class="checkout-card-body restaurant-hero">
                    <div class="restaurant-hero-icon">
                        <?php echo strtoupper(substr($restaurant['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="restaurant-name mb-1"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                        <p class="restaurant-address mb-2"><?php echo htmlspecialchars($restaurant['address']); ?></p>
                        <div class="restaurant-meta">
                            <span class="meta-pill">Estimated delivery: 30 mins</span>
                            <span class="meta-pill">Freshly prepared</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="checkout-card mb-4">
                <div class="checkout-card-body">
                    <div class="section-title-row">
                        <h4 class="section-title">Order Summary</h4>
                    </div>

                    <div class="table-responsive">
                        <table class="table checkout-table align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </td>
                                        <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-end fw-semibold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Delivery Form -->
            <form action="process_order.php" method="POST">
                <div class="checkout-card mb-4">
                    <div class="checkout-card-body">
                        <div class="section-title-row">
                            <h4 class="section-title">Delivery Information</h4>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label checkout-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control checkout-input" placeholder="Enter your phone number" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label checkout-label">Delivery Address</label>
                                <input type="text" name="address" class="form-control checkout-input" placeholder="Enter your address" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label checkout-label">Order Notes</label>
                                <textarea name="notes" class="form-control checkout-input" rows="3" placeholder="Add any instructions for the restaurant or rider"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden rider values -->
                <input type="hidden" name="rider_id" value="<?php echo (int)$rider_id; ?>">
                <input type="hidden" name="rider_name" value="<?php echo htmlspecialchars($rider_name); ?>">
                <input type="hidden" name="rider_phone" value="<?php echo htmlspecialchars($rider_phone); ?>">

                <!-- Mobile summary button -->
                <div class="d-lg-none mb-4">
                    <button type="submit" class="btn place-order-btn w-100">Place Order</button>
                </div>
            </form>
        </div>

        <!-- RIGHT SIDE -->
<div class="col-lg-4">
    <div class="right-sticky">
        <!-- Rider Info -->
        <div class="checkout-card mb-4">
            <div class="checkout-card-body">
                <div class="section-title-row">
                    <h4 class="section-title">Rider Information</h4>
                </div>

                <div class="rider-box">
                    <div class="rider-avatar">
                        <?php echo strtoupper(substr($rider_name, 0, 1)); ?>
                    </div>
                    <div>
                        <div class="rider-name"><?php echo htmlspecialchars($rider_name); ?></div>
                        <div class="rider-status">Ready for delivery</div>
                    </div>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <span>Phone</span>
                        <strong><?php echo htmlspecialchars($rider_phone); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Vehicle</span>
                        <strong><?php echo htmlspecialchars($rider_vehicle); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>ETA</span>
                        <strong><?php echo htmlspecialchars($rider_eta); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="checkout-card">
            <div class="checkout-card-body">
                <div class="section-title-row">
                    <h4 class="section-title">Payment Summary</h4>
                </div>

                <div class="info-list mb-3">
                    <div class="info-row">
                        <span>Subtotal</span>
                        <strong>$<?php echo number_format($subtotal, 2); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Delivery Fee</span>
                        <strong>$<?php echo number_format($delivery_fee, 2); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Service Fee</span>
                        <strong>$<?php echo number_format($service_fee, 2); ?></strong>
                    </div>
                </div>

                <div class="total-box">
                    <span>Total</span>
                    <strong>$<?php echo number_format($total_price, 2); ?></strong>
                </div>

                <form action="process_order.php" method="POST" class="mt-4">
                    <input type="hidden" name="phone" id="hidden-phone">
                    <input type="hidden" name="address" id="hidden-address">
                    <input type="hidden" name="notes" id="hidden-notes">

                    <input type="hidden" name="rider_id" value="<?php echo (int)$rider_id; ?>">
                    <input type="hidden" name="rider_name" value="<?php echo htmlspecialchars($rider_name); ?>">
                    <input type="hidden" name="rider_phone" value="<?php echo htmlspecialchars($rider_phone); ?>">

                    <button type="submit" class="btn place-order-btn w-100" onclick="syncDeliveryForm()">Place Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function syncDeliveryForm() {
    const phoneInput = document.querySelector('input[name="phone"]');
    const addressInput = document.querySelector('input[name="address"]');
    const notesInput = document.querySelector('textarea[name="notes"]');

    document.getElementById('hidden-phone').value = phoneInput ? phoneInput.value : '';
    document.getElementById('hidden-address').value = addressInput ? addressInput.value : '';
    document.getElementById('hidden-notes').value = notesInput ? notesInput.value : '';
}
</script>

</body>
</html>