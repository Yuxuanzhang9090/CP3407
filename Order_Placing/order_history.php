<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/CP3407/registration%20%26%20login/style.css?v=1775444152">
</head>
<body>

<div class="history-shell">
    <div class="history-header">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <span class="checkout-badge">Order Timeline</span>
                <h1 class="checkout-title mb-2">Your Order History</h1>
                <p class="checkout-subtitle mb-0">
                    Signed in as russ@gmail.com. Review your previous food orders, payment status, and item breakdowns.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="/CP3407/Browse_Restaurants/categories.php" class="btn btn-outline-dark">
                    <i class="fa-solid fa-house me-2"></i>Browse Restaurants
                </a>
            </div>
        </div>
    </div>

                        <div class="card history-card">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                    <div>
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <h3 class="h4 mb-0">McDonald&#039;s</h3>
                            <span class="text-muted">#32</span>
                        </div>
                        <div class="history-total">SGD 21.19</div>
                        <div class="history-meta">
                            <span><i class="fa-regular fa-clock me-1"></i>06 Apr 2026, 11:11 AM</span>
                            <span><i class="fa-solid fa-bag-shopping me-1"></i>2 item(s)</span>
                            <span><i class="fa-solid fa-location-dot me-1"></i>111</span>
                        </div>
                    </div>
                    <div class="history-actions text-lg-end">
                        <div class="status-stack">
                            <span class="badge bg-warning text-dark status-badge">
                                Order: Pending Payment                            </span>
                            <span class="badge bg-warning text-dark status-badge">
                                Payment: Pending                            </span>
                            <span class="badge bg-warning text-dark status-badge">
                                Split: Pending                            </span>
                        </div>
                        <a href="/CP3407/Order_Placing/order_details.php?id=32" class="btn btn-primary">
                            <i class="fa-solid fa-receipt me-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
                    <div class="card history-card">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                    <div>
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <h3 class="h4 mb-0">McDonald&#039;s</h3>
                            <span class="text-muted">#31</span>
                        </div>
                        <div class="history-total">SGD 23.09</div>
                        <div class="history-meta">
                            <span><i class="fa-regular fa-clock me-1"></i>05 Apr 2026, 11:59 PM</span>
                            <span><i class="fa-solid fa-bag-shopping me-1"></i>4 item(s)</span>
                            <span><i class="fa-solid fa-location-dot me-1"></i>blk 234</span>
                        </div>
                    </div>
                    <div class="history-actions text-lg-end">
                        <div class="status-stack">
                            <span class="badge bg-success status-badge">
                                Order: Paid                            </span>
                            <span class="badge bg-success status-badge">
                                Payment: Paid                            </span>
                            <span class="badge bg-success status-badge">
                                Split: Completed                            </span>
                        </div>
                        <a href="/CP3407/Order_Placing/order_details.php?id=31" class="btn btn-primary">
                            <i class="fa-solid fa-receipt me-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
            </div>

</body>
</html>
