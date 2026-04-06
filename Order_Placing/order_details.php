<?php
session_start();
require_once(__DIR__ . "/../config.php");

cp3407_require_login();
$user_id = cp3407_current_user_id($conn);

if ($user_id <= 0) {
    die("Unable to identify the logged-in user.");
}

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    die("Invalid order ID.");
}

$stmt = $conn->prepare("
    SELECT
        o.*,
        r.name AS restaurant_name,
        r.address AS restaurant_address,
        d.name AS rider_name,
        d.phone AS rider_phone,
        d.vehicle AS rider_vehicle
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN riders d ON o.rider_id = d.id
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result ? $result->fetch_assoc() : null;

if (!$order) {
    die("Order not found.");
}

$item_stmt = $conn->prepare("
    SELECT item_name, quantity, price
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();

$transfer_stmt = $conn->prepare("
    SELECT recipient_type, amount, stripe_transfer_id, status
    FROM transfers
    WHERE order_id = ?
    ORDER BY id ASC
");
$transfer_stmt->bind_param("i", $order_id);
$transfer_stmt->execute();
$transfers = $transfer_stmt->get_result();

function detailBadgeClass(string $status): string
{
    return match ($status) {
        'paid', 'completed' => 'success',
        'pending_payment', 'pending' => 'warning text-dark',
        'failed', 'cancelled' => 'danger',
        default => 'secondary',
    };
}

function detailLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

function detailOrderStatusText(string $status): string
{
    return match ($status) {
        'paid' => 'Order: Confirmed',
        'pending_payment' => 'Order: Waiting for Payment',
        'pending' => 'Order: Pending',
        'cancelled' => 'Order: Cancelled',
        'failed' => 'Order: Failed',
        default => 'Order: ' . detailLabel($status),
    };
}

function detailPaymentStatusText(string $status): string
{
    return match ($status) {
        'paid' => 'Payment: Paid',
        'pending' => 'Payment: Pending',
        'failed' => 'Payment: Failed',
        'cancelled' => 'Payment: Cancelled',
        default => 'Payment: ' . detailLabel($status),
    };
}

function detailSplitStatusText(string $status): string
{
    return match ($status) {
        'completed' => 'Payout: Completed',
        'pending' => 'Payout: Pending',
        'partial_failed' => 'Payout: Partially Completed',
        'failed' => 'Payout: Failed',
        default => 'Payout: ' . detailLabel($status),
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/CP3407/registration%20%26%20login/style.css?v=<?php echo filemtime(__DIR__ . '/../registration & login/style.css'); ?>">
    <style>
        body {
            background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)),
                        url("/CP3407/registration%20%26%20login/online%20food.jpg");
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .details-shell {
            max-width: 1120px;
            margin: 56px auto 90px;
            padding: 0 16px;
        }

        .details-card {
            background: #ffffff;
            border: none;
            border-radius: 24px;
            box-shadow: 0 14px 36px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .summary-box {
            border-radius: 18px;
            background: #f8fafc;
            padding: 18px 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .details-header {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        .details-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-start;
        }

        .details-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            line-height: 1.2;
            border-radius: 14px;
            white-space: nowrap;
            min-height: auto;
        }

        .summary-label {
            display: block;
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 6px;
        }

        .summary-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
        }

        .info-list p:last-child,
        .table td:last-child,
        .table th:last-child {
            margin-bottom: 0;
        }

        @media (min-width: 992px) {
            .details-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }

            .details-actions {
                justify-content: flex-end;
                flex: 0 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="details-shell">
    <div class="details-card p-4 p-lg-5">
        <div class="details-header">
            <div>
                <span class="checkout-badge">Order Details</span>
                <h1 class="checkout-title mb-2">Order #<?php echo (int)$order['id']; ?></h1>
                <p class="checkout-subtitle mb-0">
                    Placed with <?php echo htmlspecialchars($order['restaurant_name']); ?> on
                    <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>.
                </p>
            </div>
            <div class="details-actions">
                <a href="<?php echo $app_url; ?>/Order_Placing/order_history.php" class="btn btn-outline-dark">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to History
                </a>
                <a href="<?php echo $app_url; ?>/Browse_Restaurants/categories.php" class="btn btn-primary">
                    <i class="fa-solid fa-house me-2"></i>Browse Restaurants
                </a>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <span class="badge bg-<?php echo detailBadgeClass((string)$order['status']); ?> px-3 py-2">
                <?php echo htmlspecialchars(detailOrderStatusText((string)$order['status'])); ?>
            </span>
            <span class="badge bg-<?php echo detailBadgeClass((string)$order['payment_status']); ?> px-3 py-2">
                <?php echo htmlspecialchars(detailPaymentStatusText((string)$order['payment_status'])); ?>
            </span>
            <span class="badge bg-<?php echo detailBadgeClass((string)($order['split_status'] ?? 'pending')); ?> px-3 py-2">
                <?php echo htmlspecialchars(detailSplitStatusText((string)($order['split_status'] ?? 'pending'))); ?>
            </span>
        </div>

        <div class="row g-3 align-items-start mb-4">
            <div class="col-md-4">
                <div class="summary-box">
                    <span class="summary-label">Total Paid</span>
                    <span class="summary-value">SGD <?php echo number_format((float)$order['total_price'], 2); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-box">
                    <span class="summary-label">Restaurant</span>
                    <span class="summary-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-box">
                    <span class="summary-label">Rider</span>
                    <span class="summary-value"><?php echo htmlspecialchars($order['rider_name'] ?: 'Pending rider'); ?></span>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="summary-box mb-4">
                    <h3 class="h5 mb-3">Items Ordered</h3>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                                        <td class="text-end">SGD <?php echo number_format((float)$item['price'], 2); ?></td>
                                        <td class="text-end">SGD <?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="summary-box">
                    <h3 class="h5 mb-3">Delivery & Payment</h3>
                    <div class="info-list">
                        <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes'] !== '' ? $order['notes'] : 'No special instructions.'); ?></p>
                        <p><strong>Stripe Checkout Session:</strong> <?php echo htmlspecialchars($order['stripe_checkout_session_id'] ?: 'N/A'); ?></p>
                        <p><strong>Stripe Payment Intent:</strong> <?php echo htmlspecialchars($order['stripe_payment_intent_id'] ?: 'N/A'); ?></p>
                        <?php if (!empty($order['split_error'])): ?>
                            <p><strong>Split Error:</strong> <?php echo htmlspecialchars($order['split_error']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-box mb-4">
                    <h3 class="h5 mb-3">Price Summary</h3>
                    <p class="d-flex justify-content-between"><span>Subtotal</span><strong>SGD <?php echo number_format((float)$order['subtotal'], 2); ?></strong></p>
                    <p class="d-flex justify-content-between"><span>Delivery Fee</span><strong>SGD <?php echo number_format((float)$order['delivery_fee'], 2); ?></strong></p>
                    <p class="d-flex justify-content-between"><span>Service Fee</span><strong>SGD <?php echo number_format((float)$order['service_fee'], 2); ?></strong></p>
                    <hr>
                    <p class="d-flex justify-content-between mb-0"><span>Total</span><strong>SGD <?php echo number_format((float)$order['total_price'], 2); ?></strong></p>
                </div>

                <div class="summary-box mb-4">
                    <h3 class="h5 mb-3">Payout Split</h3>
                    <p class="d-flex justify-content-between"><span>Restaurant</span><strong>SGD <?php echo number_format((float)$order['merchant_amount'], 2); ?></strong></p>
                    <p class="d-flex justify-content-between"><span>Rider</span><strong>SGD <?php echo number_format((float)$order['rider_amount'], 2); ?></strong></p>
                    <p class="d-flex justify-content-between mb-0"><span>Platform</span><strong>SGD <?php echo number_format((float)$order['platform_fee'], 2); ?></strong></p>
                </div>

                <div class="summary-box">
                    <h3 class="h5 mb-3">Transfer Records</h3>
                    <?php if ($transfers->num_rows > 0): ?>
                        <?php while ($transfer = $transfers->fetch_assoc()): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <p class="mb-1"><strong><?php echo htmlspecialchars(detailLabel((string)$transfer['recipient_type'])); ?></strong></p>
                                <p class="mb-1">Amount: SGD <?php echo number_format((float)$transfer['amount'], 2); ?></p>
                                <p class="mb-1">Status: <?php echo htmlspecialchars(detailLabel((string)$transfer['status'])); ?></p>
                                <p class="mb-0">Transfer ID: <?php echo htmlspecialchars($transfer['stripe_transfer_id'] ?: 'N/A'); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="mb-0 text-muted">No transfer records have been created for this order yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
