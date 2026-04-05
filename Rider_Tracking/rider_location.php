<?php
session_start();
require_once("../config.php");

if (!isset($_GET['order_id']) || !isset($_GET['rider_id'])) {
    die("Missing order_id or rider_id.");
}

$order_id = (int)$_GET['order_id'];
$rider_id = (int)$_GET['rider_id'];

if ($order_id <= 0 || $rider_id <= 0) {
    die("Invalid order_id or rider_id.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Live Location</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            padding: 30px;
        }

        .card {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        h2 {
            margin-top: 0;
        }

        .row {
            margin-bottom: 12px;
            line-height: 1.7;
        }

        .status {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #eef5ff;
            color: #1d4ed8;
            font-weight: bold;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .ok {
            background: #dcfce7;
            color: #15803d;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Rider Live Location</h2>

        <div class="row"><strong>Order ID:</strong> <?php echo $order_id; ?></div>
        <div class="row"><strong>Rider ID:</strong> <?php echo $rider_id; ?></div>
        <div class="row"><strong>Latitude:</strong> <span id="lat">Waiting...</span></div>
        <div class="row"><strong>Longitude:</strong> <span id="lng">Waiting...</span></div>
        <div class="row"><strong>Last Upload:</strong> <span id="last-upload">Not uploaded yet</span></div>

        <div class="status" id="status-box">Requesting location permission...</div>
    </div>

    <script>
        const orderId = <?php echo $order_id; ?>;
        const riderId = <?php echo $rider_id; ?>;

        function setStatus(message, type = '') {
            const box = document.getElementById('status-box');
            box.textContent = message;
            box.className = 'status';
            if (type) {
                box.classList.add(type);
            }
        }

        async function uploadLocation(lat, lng) {
            try {
                const response = await fetch('update_rider_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        rider_id: riderId,
                        latitude: lat,
                        longitude: lng
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('last-upload').textContent = data.created_at || new Date().toLocaleString();
                    setStatus('Location uploaded successfully.', 'ok');
                } else {
                    setStatus(data.error || 'Upload failed.', 'error');
                }
            } catch (error) {
                setStatus('Failed to upload location.', 'error');
                console.error(error);
            }
        }

        function startTracking() {
            if (!navigator.geolocation) {
                setStatus('Geolocation is not supported by this browser.', 'error');
                return;
            }

            navigator.geolocation.watchPosition(
                async function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    document.getElementById('lat').textContent = lat.toFixed(6);
                    document.getElementById('lng').textContent = lng.toFixed(6);

                    await uploadLocation(lat, lng);
                },
                function(error) {
                    let msg = 'Location access failed.';
                    if (error.code === 1) msg = 'Permission denied.';
                    if (error.code === 2) msg = 'Location unavailable.';
                    if (error.code === 3) msg = 'Location request timed out.';
                    setStatus(msg, 'error');
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 10000
                }
            );
        }

        startTracking();
    </script>
</body>
</html>