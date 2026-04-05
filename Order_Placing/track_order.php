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

$icon_links = [
    'pending' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/receipt.svg',
    'confirmed' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/check.svg',
    'preparing' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/chef-hat.svg',
    'ready_for_pickup' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/package.svg',
    'picked_up' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/motorbike.svg',
    'on_the_way' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/map-pin.svg',
    'delivered' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/checks.svg',
    'cancelled' => 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/x.svg'
];

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

$history_items = [];
while ($item = $history_result->fetch_assoc()) {
    $history_items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""
    />
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        crossorigin=""
    ></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .page-container {
            max-width: 1380px;
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

        .top-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .full-width {
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        h2, h3 {
            margin-top: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 30px;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .badge-pending { background: #6b7280; }
        .badge-confirmed { background: #f59e0b; }
        .badge-preparing { background: #f97316; }
        .badge-ready_for_pickup { background: #fb923c; }
        .badge-picked_up { background: #0ea5e9; }
        .badge-on_the_way { background: #3b82f6; }
        .badge-delivered { background: #22c55e; }
        .badge-cancelled { background: #ef4444; }

        .detail-row {
            margin-bottom: 10px;
            line-height: 1.7;
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

        .muted {
            color: #777;
        }

        #mini-map {
            width: 100%;
            height: 300px;
            border-radius: 12px;
            overflow: hidden;
            margin: 14px 0 16px;
            border: 1px solid #dce8ff;
        }

        .map-meta {
            margin-top: 4px;
        }

        .tracking-wrapper {
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .tracking-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0;
            min-width: 1380px;
            margin-top: 12px;
        }

        .tracking-item {
            display: flex;
            align-items: flex-start;
            flex: 1;
            min-width: 185px;
            position: relative;
        }

        .tracking-node {
            width: 130px;
            text-align: center;
            flex-shrink: 0;
        }

        .tracking-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            border: 2px solid #d1d5db;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .tracking-icon img {
            width: 24px;
            height: 24px;
            display: block;
        }

        .tracking-label {
            font-size: 14px;
            font-weight: bold;
            color: #444;
            line-height: 1.35;
            min-height: 38px;
            padding: 0 4px;
        }

        .tracking-time {
            font-size: 12px;
            color: #888;
            margin-top: 8px;
            min-height: 34px;
            padding: 0 6px;
            line-height: 1.35;
        }

        .tracking-note {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
            line-height: 1.4;
            padding: 0 6px;
            min-height: 50px;
        }

        .tracking-connector {
            flex: 1;
            height: 6px;
            background: #d1d5db;
            border-radius: 999px;
            margin-top: 22px;
            margin-left: 14px;
            margin-right: 14px;
            position: relative;
            overflow: hidden;
            min-width: 54px;
        }

        .tracking-connector-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0%;
            background: #ffb38a;
            border-radius: 999px;
        }

        .tracking-item.completed .tracking-icon {
            background: #ffb38a;
            border-color: #ffb38a;
        }

        .tracking-item.completed .tracking-label {
            color: #222;
        }

        .tracking-item.completed .tracking-connector-fill {
            width: 100%;
        }

        .tracking-item.current .tracking-icon {
            background: #ffb38a;
            border-color: #ffb38a;
            animation: currentPulse 1.8s ease-in-out infinite;
        }

        .tracking-item.current .tracking-label {
            color: #222;
        }

        .tracking-item.current .tracking-connector-fill {
            animation: peachFlow 6s linear forwards;
        }

        .tracking-item.upcoming .tracking-icon {
            background: #e5e7eb;
            border-color: #d1d5db;
        }

        .tracking-item.upcoming .tracking-label {
            color: #777;
        }

        @keyframes peachFlow {
            from { width: 0%; }
            to { width: 100%; }
        }

        @keyframes currentPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 179, 138, 0.45);
                transform: scale(1);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 179, 138, 0);
                transform: scale(1.04);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 179, 138, 0);
                transform: scale(1);
            }
        }

        .mini-timeline {
            margin-top: 28px;
        }

        .mini-item {
            border-left: 4px solid #ffb38a;
            padding: 10px 14px;
            margin-bottom: 12px;
            background: #fafafa;
            border-radius: 10px;
        }

        .mini-item strong {
            display: block;
            margin-bottom: 4px;
        }

        .mini-time {
            color: #777;
            font-size: 13px;
            margin-top: 4px;
        }

        .rider-marker-wrap {
            background: transparent;
            border: none;
        }

        .rider-marker {
            position: relative;
            width: 38px;
            height: 38px;
        }

        .rider-marker-core {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            border: 3px solid #ffffff;
        }

        .rider-marker-core img {
            width: 20px;
            height: 20px;
            display: block;
            filter: brightness(0) invert(1);
        }

        .rider-marker-pulse {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.22);
            animation: riderPulse 1.8s infinite;
            z-index: 1;
        }

        @keyframes riderPulse {
            0% {
                transform: scale(1);
                opacity: 0.85;
            }
            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        @media (max-width: 980px) {
            .top-grid {
                grid-template-columns: 1fr;
            }

            .tracking-row {
                min-width: 1100px;
            }
        }
    </style>
</head>
<body>
<div class="page-container">

    <div class="top-bar">
    <a href="/CP3407/Browse_Restaurants/categories.php"><- Back to categories</a>
    </div>

    <div class="top-grid">
        <div class="card">
            <h2>Track Order #<?php echo (int)$order['id']; ?></h2>

            <div class="detail-row"><strong>Restaurant:</strong> <?php echo htmlspecialchars($order['restaurant_name']); ?></div>
            <div class="detail-row"><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></div>
            <div class="detail-row">
                <strong>Current Status:</strong>
                <span class="status-badge badge-<?php echo htmlspecialchars($order['order_status']); ?>" id="current-status">
                    <?php echo htmlspecialchars($status_labels[$order['order_status']] ?? $order['order_status']); ?>
                </span>
            </div>
            <div class="detail-row">
                <strong>Estimated Delivery Time:</strong>
                <span id="eta"><?php echo !empty($order['estimated_delivery_time']) ? htmlspecialchars($order['estimated_delivery_time']) : 'Not available'; ?></span>
            </div>
        </div>

        <div class="card">
            <h3>Rider Information</h3>
            <div class="detail-row"><strong>Name:</strong> <span id="rider-name"><?php echo !empty($order['rider_name']) ? htmlspecialchars($order['rider_name']) : 'Not assigned yet'; ?></span></div>
            <div class="detail-row"><strong>Phone:</strong> <span id="rider-phone"><?php echo !empty($order['rider_phone']) ? htmlspecialchars($order['rider_phone']) : 'Not available'; ?></span></div>
            <div class="detail-row"><strong>Vehicle:</strong> <span id="rider-vehicle"><?php echo !empty($order['rider_vehicle']) ? htmlspecialchars($order['rider_vehicle']) : 'Not available'; ?></span></div>
        </div>
    </div>

    <div class="card full-width">
        <h3>Order Tracking</h3>

        <div class="tracking-wrapper">
            <div class="tracking-row" id="tracking-row">
                <?php foreach ($steps as $index => $step): ?>
                    <?php
                    $matched = null;
                    foreach ($history_items as $history_item) {
                        if ($history_item['status'] === $step) {
                            $matched = $history_item;
                            break;
                        }
                    }

                    $state_class = 'upcoming';
                    if ($index < $current_step_index) {
                        $state_class = 'completed';
                    } elseif ($index === $current_step_index) {
                        $state_class = 'current';
                    }
                    ?>
                    <div class="tracking-item <?php echo $state_class; ?>" data-step="<?php echo htmlspecialchars($step); ?>">
                        <div class="tracking-node">
                            <div class="tracking-icon">
                                <img src="<?php echo htmlspecialchars($icon_links[$step] ?? ''); ?>" alt="<?php echo htmlspecialchars($step); ?>">
                            </div>
                            <div class="tracking-label">
                                <?php echo htmlspecialchars($status_labels[$step] ?? $step); ?>
                            </div>
                            <div class="tracking-time">
                                <?php echo ($matched && !empty($matched['created_at'])) ? htmlspecialchars($matched['created_at']) : ''; ?>
                            </div>
                            <div class="tracking-note">
                                <?php
                                if ($matched && !empty($matched['notes'])) {
                                    echo htmlspecialchars($matched['notes']);
                                } elseif ($state_class === 'current') {
                                    echo 'Currently in progress...';
                                } else {
                                    echo 'Waiting for update';
                                }
                                ?>
                            </div>
                        </div>

                        <?php if ($index < count($steps) - 1): ?>
                            <div class="tracking-connector">
                                <div class="tracking-connector-fill"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mini-timeline" id="mini-timeline">
            <?php foreach ($history_items as $item): ?>
                <div class="mini-item">
                    <strong><?php echo htmlspecialchars($status_labels[$item['status']] ?? $item['status']); ?></strong>
                    <div><?php echo !empty($item['notes']) ? htmlspecialchars($item['notes']) : 'No notes'; ?></div>
                    <div class="mini-time"><?php echo htmlspecialchars($item['created_at']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card full-width">
        <h3>Live Rider Location</h3>
        <div class="map-box">
            <div class="location-big">Rider Location</div>

            <div id="mini-map"></div>

            <div class="detail-row map-meta">
                <strong>Latitude:</strong>
                <span id="lat">N/A</span>
                &nbsp;&nbsp;&nbsp;
                <strong>Longitude:</strong>
                <span id="lng">N/A</span>
            </div>

            <div class="detail-row">
                <strong>Last Updated:</strong>
                <span id="location-updated"><?php echo $latest_location ? htmlspecialchars($latest_location['created_at']) : 'N/A'; ?></span>
            </div>

            <p class="muted" id="location-message">The page refreshes tracking data automatically.</p>
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

const iconLinks = {
    pending: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/receipt.svg',
    confirmed: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/check.svg',
    preparing: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/chef-hat.svg',
    ready_for_pickup: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/package.svg',
    picked_up: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/motorbike.svg',
    on_the_way: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/map-pin.svg',
    delivered: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/checks.svg',
    cancelled: 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/x.svg'
};

const riderMapIconUrl = 'https://cdn.jsdelivr.net/npm/@tabler/icons/icons/motorbike.svg';

const restaurantPoint = {
    lat: 1.2820,
    lng: 103.8435
};

const customerPoint = {
    lat: 1.2765,
    lng: 103.8547
};

let miniMap = null;
let riderMarker = null;
let riderAccuracyCircle = null;
let riderTrail = null;
let animationFrameId = null;
let currentRouteProgress = 0;
let targetRouteProgress = 0;
let isDeliveredLocked = false;

function badgeClassByStatus(status) {
    const allowed = [
        'pending',
        'confirmed',
        'preparing',
        'ready_for_pickup',
        'picked_up',
        'on_the_way',
        'delivered',
        'cancelled'
    ];
    return allowed.includes(status) ? `badge-${status}` : 'badge-pending';
}

function getProgressByStatus(status) {
    switch (status) {
        case 'pending':
            return 0.00;
        case 'confirmed':
            return 0.08;
        case 'preparing':
            return 0.18;
        case 'ready_for_pickup':
            return 0.30;
        case 'picked_up':
            return 0.50;
        case 'on_the_way':
            return 0.82;
        case 'delivered':
            return 1.00;
        default:
            return 0.00;
    }
}

function interpolatePosition(progress) {
    const lat = restaurantPoint.lat + (customerPoint.lat - restaurantPoint.lat) * progress;
    const lng = restaurantPoint.lng + (customerPoint.lng - restaurantPoint.lng) * progress;
    return { lat, lng };
}

const initialStatus = <?php echo json_encode($order['order_status']); ?>;
currentRouteProgress = getProgressByStatus(initialStatus);
targetRouteProgress = currentRouteProgress;
const initialRoutePos = interpolatePosition(currentRouteProgress);

if (initialStatus === 'delivered') {
    isDeliveredLocked = true;
}

function initMiniMap() {
    miniMap = L.map('mini-map').setView([initialRoutePos.lat, initialRoutePos.lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(miniMap);

    const riderIcon = L.divIcon({
        className: 'rider-marker-wrap',
        html: `
            <div class="rider-marker">
                <div class="rider-marker-pulse"></div>
                <div class="rider-marker-core">
                    <img src="${riderMapIconUrl}" alt="Rider">
                </div>
            </div>
        `,
        iconSize: [38, 38],
        iconAnchor: [19, 19]
    });

    riderMarker = L.marker(
        [initialRoutePos.lat, initialRoutePos.lng],
        { icon: riderIcon }
    ).addTo(miniMap).bindPopup('Rider current location');

    riderAccuracyCircle = L.circle([initialRoutePos.lat, initialRoutePos.lng], {
        radius: 45,
        color: '#3b82f6',
        fillColor: '#93c5fd',
        fillOpacity: 0.18,
        weight: 1
    }).addTo(miniMap);

    const initialTrail = isDeliveredLocked
        ? [
            [restaurantPoint.lat, restaurantPoint.lng],
            [customerPoint.lat, customerPoint.lng]
          ]
        : [
            [restaurantPoint.lat, restaurantPoint.lng],
            [initialRoutePos.lat, initialRoutePos.lng]
          ];

    riderTrail = L.polyline(initialTrail, {
        color: '#2563eb',
        weight: 4,
        opacity: 0.75
    }).addTo(miniMap);

    setTimeout(() => {
        miniMap.invalidateSize();
    }, 200);

    updateCoordinateDisplay(initialRoutePos.lat, initialRoutePos.lng);
}

function updateCoordinateDisplay(lat, lng) {
    document.getElementById("lat").innerText = lat.toFixed(6);
    document.getElementById("lng").innerText = lng.toFixed(6);
}

function animateProgressTo(newTargetProgress, duration = 6500) {
    if (isDeliveredLocked) {
        return;
    }

    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    const startProgress = currentRouteProgress;
    const startTime = performance.now();

    function easeInOut(t) {
        return t < 0.5
            ? 2 * t * t
            : 1 - Math.pow(-2 * t + 2, 2) / 2;
    }

    function step(now) {
        if (isDeliveredLocked) {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
            return;
        }

        const elapsed = now - startTime;
        const raw = Math.min(elapsed / duration, 1);
        const eased = easeInOut(raw);

        currentRouteProgress = startProgress + (newTargetProgress - startProgress) * eased;

        const pos = interpolatePosition(currentRouteProgress);

        if (riderMarker) {
            riderMarker.setLatLng([pos.lat, pos.lng]);
        }

        if (riderAccuracyCircle) {
            riderAccuracyCircle.setLatLng([pos.lat, pos.lng]);
        }

        if (riderTrail) {
            const trailBase = [[restaurantPoint.lat, restaurantPoint.lng]];
            const trailSteps = 20;

            for (let i = 1; i <= trailSteps; i++) {
                const p = currentRouteProgress * (i / trailSteps);
                const point = interpolatePosition(p);
                trailBase.push([point.lat, point.lng]);
            }

            riderTrail.setLatLngs(trailBase);
        }

        if (miniMap) {
            miniMap.panTo([pos.lat, pos.lng], {
                animate: true,
                duration: 1.5
            });
        }

        updateCoordinateDisplay(pos.lat, pos.lng);

        if (raw < 1) {
            animationFrameId = requestAnimationFrame(step);
        } else {
            animationFrameId = null;
        }
    }

    animationFrameId = requestAnimationFrame(step);
}

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

        const currentStatusEl = document.getElementById("current-status");
        currentStatusEl.innerText = data.status_text || data.status;
        currentStatusEl.className = `status-badge ${badgeClassByStatus(data.status)}`;

        document.getElementById("eta").innerText = data.estimated_delivery_time || "Not available";

        document.getElementById("rider-name").innerText = (data.rider && data.rider.name) ? data.rider.name : "Not assigned yet";
        document.getElementById("rider-phone").innerText = (data.rider && data.rider.phone) ? data.rider.phone : "Not available";
        document.getElementById("rider-vehicle").innerText = (data.rider && data.rider.vehicle) ? data.rider.vehicle : "Not available";

        const progress = getProgressByStatus(data.status);
        targetRouteProgress = progress;

        if (data.location && data.location.created_at) {
            document.getElementById("location-updated").innerText = data.location.created_at;
        } else {
            document.getElementById("location-updated").innerText = "Simulated by order status";
        }

        if (data.status === "delivered") {
            isDeliveredLocked = true;

            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }

            currentRouteProgress = 1.00;
            targetRouteProgress = 1.00;

            const finalPos = interpolatePosition(1.00);

            if (riderMarker) {
                riderMarker.setLatLng([finalPos.lat, finalPos.lng]);
            }

            if (riderAccuracyCircle) {
                riderAccuracyCircle.setLatLng([finalPos.lat, finalPos.lng]);
            }

            if (riderTrail) {
                riderTrail.setLatLngs([
                    [restaurantPoint.lat, restaurantPoint.lng],
                    [customerPoint.lat, customerPoint.lng]
                ]);
            }

            if (miniMap) {
                miniMap.panTo([finalPos.lat, finalPos.lng], {
                    animate: true,
                    duration: 1
                });
            }

            updateCoordinateDisplay(finalPos.lat, finalPos.lng);
        } else {
            isDeliveredLocked = false;

            if (Math.abs(targetRouteProgress - currentRouteProgress) > 0.001) {
                animateProgressTo(targetRouteProgress, 6500);
            } else {
                const pos = interpolatePosition(targetRouteProgress);

                if (riderMarker) {
                    riderMarker.setLatLng([pos.lat, pos.lng]);
                }

                if (riderAccuracyCircle) {
                    riderAccuracyCircle.setLatLng([pos.lat, pos.lng]);
                }

                updateCoordinateDisplay(pos.lat, pos.lng);
            }
        }

        const currentIndex = steps.indexOf(data.status);

        const trackingRow = document.getElementById("tracking-row");
        trackingRow.innerHTML = "";

        steps.forEach((step, index) => {
            const matched = data.history ? data.history.find(item => item.status === step) : null;

            let stateClass = "upcoming";
            if (index < currentIndex) {
                stateClass = "completed";
            } else if (index === currentIndex) {
                stateClass = "current";
            }

            const timeText = matched && matched.created_at ? matched.created_at : "";
            const noteText = matched && matched.notes
                ? matched.notes
                : (stateClass === "current" ? "Currently in progress..." : "Waiting for update");

            const connectorHtml = index < steps.length - 1
                ? `<div class="tracking-connector"><div class="tracking-connector-fill"></div></div>`
                : '';

            const item = document.createElement("div");
            item.className = `tracking-item ${stateClass}`;
            item.innerHTML = `
                <div class="tracking-node">
                    <div class="tracking-icon">
                        <img src="${iconLinks[step] || ''}" alt="${step}">
                    </div>
                    <div class="tracking-label">${statusLabels[step] || step}</div>
                    <div class="tracking-time">${timeText}</div>
                    <div class="tracking-note">${noteText}</div>
                </div>
                ${connectorHtml}
            `;
            trackingRow.appendChild(item);
        });

        const miniTimeline = document.getElementById("mini-timeline");
        miniTimeline.innerHTML = "";

        if (data.history && data.history.length > 0) {
            data.history.forEach(item => {
                const div = document.createElement("div");
                div.className = "mini-item";
                div.innerHTML = `
                    <strong>${statusLabels[item.status] || item.status}</strong>
                    <div>${item.notes ? item.notes : 'No notes'}</div>
                    <div class="mini-time">${item.created_at}</div>
                `;
                miniTimeline.appendChild(div);
            });
        }

        const locationMessage = document.getElementById("location-message");
        if (data.status === "delivered") {
            locationMessage.innerText = "Your order has been delivered. Enjoy your meal!";
        } else if (data.status === "on_the_way") {
            locationMessage.innerText = "Your rider is on the way.";
        } else if (data.status === "picked_up") {
            locationMessage.innerText = "Your order has been picked up by the rider.";
        } else if (data.status === "ready_for_pickup") {
            locationMessage.innerText = "Your order is ready for pickup.";
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
    const currentStatusText = document.getElementById("current-status").innerText.toLowerCase();

    if (currentStatusText.includes("delivered")) {
        await refreshTracking();
        return;
    }

    await autoAdvanceOrder();
    await refreshTracking();
}

initMiniMap();
refreshTracking();
setInterval(runTrackingLoop, 5000);
</script>
</body>
</html>