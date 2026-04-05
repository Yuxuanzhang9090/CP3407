<?php
session_start();
require_once("../config.php");
require_once("../Tracking_Order/order_helpers.php");

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
}

$status_labels = getOrderStatusLabels();
$steps = getTrackingSteps();

$stmt = $conn->prepare("
    SELECT o.*,
           r.name AS restaurant_name,
           d.name AS rider_name,
           d.phone AS rider_phone,
           d.vehicle AS rider_vehicle
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN riders d ON o.rider_id = d.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();
$stmt->close();

$stmtHistory = $conn->prepare("
    SELECT status, updated_by, notes, created_at
    FROM order_status_history
    WHERE order_id = ?
    ORDER BY created_at ASC
");
$stmtHistory->bind_param("i", $order_id);
$stmtHistory->execute();
$history_result = $stmtHistory->get_result();

$stmtLocation = $conn->prepare("
    SELECT latitude, longitude, created_at
    FROM order_tracking
    WHERE order_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmtLocation->bind_param("i", $order_id);
$stmtLocation->execute();
$location_result = $stmtLocation->get_result();
$latest_location = $location_result->fetch_assoc();

$current_step_index = getStepIndex($order['order_status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .page-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .top-bar {
            margin-bottom: 20px;
        }

        .top-bar a {
            text-decoration: none;
            color: #fff;
            background: #333;
            padding: 10px 16px;
            border-radius: 8px;
            display: inline-block;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        h2, h3 {
            margin-top: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 30px;
            background: #28a745;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }

        .steps {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .step {
            flex: 1 1 120px;
            min-width: 120px;
            text-align: center;
            padding: 14px 10px;
            border-radius: 12px;
            background: #e9ecef;
            color: #666;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .step.active {
            background: #28a745;
            color: #fff;
        }

        .step.current {
            box-shadow: 0 0 0 3px rgba(40,167,69,0.18);
        }

        .detail-row {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .map-box {
            background: linear-gradient(135deg, #f8fbff, #eef5ff);
            border: 1px solid #dce8ff;
            border-radius: 14px;
            padding: 18px;
        }

        .location-big {
            font-size: 18px;
            font-weight: bold;
            color: #114a9f;
            margin-bottom: 8px;
        }

        .timeline {
            margin-top: 15px;
        }

        .timeline-item {
            border-left: 4px solid #28a745;
            padding: 12px 14px;
            margin-bottom: 12px;
            background: #fafafa;
            border-radius: 8px;
        }

        .timeline-item strong {
            display: block;
            margin-bottom: 5px;
        }

        .timeline-time {
            color: #666;
            font-size: 13px;
        }

        .muted {
            color: #777;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page-container">

    <div class="top-bar">
        <a href="/CP3407/Online%20Payment/success.php?order_id=<?php echo (int)$order_id; ?>">← Back</a>
    </div>

    <div class="grid">
        <div>
            <div class="card">
                <h2>Track Order #<?php echo (int)$order['id']; ?></h2>

                <div class="detail-row"><strong>Restaurant:</strong> <?php echo htmlspecialchars($order['restaurant_name']); ?></div>
                <div class="detail-row"><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></div>
                <div class="detail-row">
                    <strong>Current Status:</strong>
                    <span class="status-badge" id="current-status">
                        <?php echo htmlspecialchars($status_labels[$order['order_status']] ?? $order['order_status']); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <strong>Estimated Delivery Time:</strong>
                    <span id="eta"><?php echo !empty($order['estimated_delivery_time']) ? htmlspecialchars($order['estimated_delivery_time']) : 'Not available'; ?></span>
                </div>
            </div>

            <div class="card">
                <h3>Order Progress</h3>
                <div class="steps" id="steps-container">
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="step <?php echo ($index <= $current_step_index) ? 'active' : ''; ?> <?php echo ($index === $current_step_index) ? 'current' : ''; ?>" data-step="<?php echo htmlspecialchars($step); ?>">
                            <?php echo htmlspecialchars($status_labels[$step]); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>Status Timeline</h3>
                <div class="timeline" id="timeline-container">
                    <?php while ($item = $history_result->fetch_assoc()): ?>
                        <div class="timeline-item">
                            <strong><?php echo htmlspecialchars($status_labels[$item['status']] ?? $item['status']); ?></strong>
                            <div><?php echo !empty($item['notes']) ? htmlspecialchars($item['notes']) : '<span class="muted">No notes</span>'; ?></div>
                            <div class="timeline-time"><?php echo htmlspecialchars($item['created_at']); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h3>Rider Information</h3>
                <div class="detail-row"><strong>Name:</strong> <span id="rider-name"><?php echo !empty($order['rider_name']) ? htmlspecialchars($order['rider_name']) : 'Not assigned yet'; ?></span></div>
                <div class="detail-row"><strong>Phone:</strong> <span id="rider-phone"><?php echo !empty($order['rider_phone']) ? htmlspecialchars($order['rider_phone']) : 'Not available'; ?></span></div>
                <div class="detail-row"><strong>Vehicle:</strong> <span id="rider-vehicle"><?php echo !empty($order['rider_vehicle']) ? htmlspecialchars($order['rider_vehicle']) : 'Not available'; ?></span></div>
            </div>

            <div class="card">
                <h3>Live Rider Location</h3>
                <div class="map-box">
                    <div class="location-big">Rider Location</div>
                    <div class="detail-row"><strong>Latitude:</strong> <span id="lat"><?php echo $latest_location ? htmlspecialchars($latest_location['latitude']) : 'N/A'; ?></span></div>
                    <div class="detail-row"><strong>Longitude:</strong> <span id="lng"><?php echo $latest_location ? htmlspecialchars($latest_location['longitude']) : 'N/A'; ?></span></div>
                    <div class="detail-row"><strong>Last Updated:</strong> <span id="location-updated"><?php echo $latest_location ? htmlspecialchars($latest_location['created_at']) : 'N/A'; ?></span></div>
                    <p class="muted" id="location-message">The page refreshes tracking data automatically every 10 seconds.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const statusLabels = {
    pending: 'Order Placed',
    confirmed: 'Confirmed by Restaurant',
    preparing: 'Preparing Your Food',
    ready_for_pickup: 'Ready for Pickup',
    picked_up: 'Picked Up by Rider',
    on_the_way: 'On the Way',
    delivered: 'Delivered',
    cancelled: 'Cancelled'
};

const steps = [
    'pending',
    'confirmed',
    'preparing',
    'ready_for_pickup',
    'picked_up',
    'on_the_way',
    'delivered'
];

async function autoAdvanceOrder() {
    const orderId = <?php echo (int)$order_id; ?>;

    try {
        await fetch("/CP3407/Tracking_Order/auto_update_order.php?order_id=" + orderId);
    } catch (error) {
        console.error("Auto update failed:", error);
    }
}

async function refreshTracking() {
    const orderId = <?php echo (int)$order_id; ?>;

    try {
        const response = await fetch("get_tracking_data.php?order_id=" + orderId);
        const data = await response.json();

        if (data.error) {
            console.error(data.error);
            return;
        }

        document.getElementById("current-status").innerText = data.status_text || data.status;
        document.getElementById("eta").innerText = data.estimated_delivery_time || "Not available";

        document.getElementById("rider-name").innerText = (data.rider && data.rider.name) ? data.rider.name : "Not assigned yet";
        document.getElementById("rider-phone").innerText = (data.rider && data.rider.phone) ? data.rider.phone : "Not available";
        document.getElementById("rider-vehicle").innerText = (data.rider && data.rider.vehicle) ? data.rider.vehicle : "Not available";

        if (data.location) {
            document.getElementById("lat").innerText = data.location.latitude ?? "N/A";
            document.getElementById("lng").innerText = data.location.longitude ?? "N/A";
            document.getElementById("location-updated").innerText = data.location.created_at ?? "N/A";
        } else {
            document.getElementById("lat").innerText = "N/A";
            document.getElementById("lng").innerText = "N/A";
            document.getElementById("location-updated").innerText = "N/A";
        }

        const currentIndex = steps.indexOf(data.status);
        document.querySelectorAll(".step").forEach((el, index) => {
            el.classList.remove("active", "current");

            if (index <= currentIndex) {
                el.classList.add("active");
            }
            if (index === currentIndex) {
                el.classList.add("current");
            }
        });

        const timelineContainer = document.getElementById("timeline-container");
        timelineContainer.innerHTML = "";

        if (data.history && data.history.length > 0) {
            data.history.forEach(item => {
                const div = document.createElement("div");
                div.className = "timeline-item";
                div.innerHTML = `
                    <strong>${statusLabels[item.status] || item.status}</strong>
                    <div>${item.notes ? item.notes : 'No notes'}</div>
                    <div class="timeline-time">${item.created_at}</div>
                `;
                timelineContainer.appendChild(div);
            });
        }

        const locationMessage = document.getElementById("location-message");
        if (data.status === "delivered") {
            locationMessage.innerText = "Your order has been delivered. Enjoy your meal!";
        } else if (data.status === "on_the_way") {
            locationMessage.innerText = "Your rider is on the way.";
        } else if (data.status === "picked_up") {
            locationMessage.innerText = "Your order has been picked up by the rider.";
        } else if (data.status === "preparing") {
            locationMessage.innerText = "The restaurant is preparing your food.";
        } else if (data.status === "confirmed") {
            locationMessage.innerText = "The restaurant has confirmed your order.";
        } else {
            locationMessage.innerText = "The page refreshes tracking data automatically.";
        }

    } catch (error) {
        console.error("Tracking refresh failed:", error);
    }
}

async function runTrackingLoop() {
    await autoAdvanceOrder();
    await refreshTracking();
}

runTrackingLoop();
setInterval(runTrackingLoop, 5000);
</script>
</body>
</html>