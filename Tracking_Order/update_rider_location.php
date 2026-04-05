<?php
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/order_helpers.php");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $rider_id = isset($_POST['rider_id']) ? (int)$_POST['rider_id'] : 0;
    $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : 0;
    $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : 0;

    if ($order_id > 0 && $rider_id > 0) {
        insertRiderLocation($conn, $order_id, $rider_id, $latitude, $longitude);
        $message = "Rider location updated successfully.";
    } else {
        $message = "Invalid input.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Rider Location</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f6f6;
            padding: 30px;
        }
        .container {
            max-width: 600px;
            background: #fff;
            margin: 0 auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h2 {
            margin-top: 0;
        }
        label {
            display: block;
            margin-top: 14px;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        button {
            margin-top: 18px;
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #0069d9;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            background: #eef5ff;
            color: #114a9f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Rider Location</h2>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="order_id">Order ID</label>
            <input type="number" name="order_id" id="order_id" required>

            <label for="rider_id">Rider ID</label>
            <input type="number" name="rider_id" id="rider_id" required>

            <label for="latitude">Latitude</label>
            <input type="text" name="latitude" id="latitude" required>

            <label for="longitude">Longitude</label>
            <input type="text" name="longitude" id="longitude" required>

            <button type="submit">Update Location</button>
        </form>
    </div>
</body>
</html>