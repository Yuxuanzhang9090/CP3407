<?php
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/order_helpers.php");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    $allowed_statuses = [
        'pending',
        'confirmed',
        'preparing',
        'ready_for_pickup',
        'picked_up',
        'on_the_way',
        'delivered',
        'cancelled'
    ];

    if ($order_id > 0 && in_array($new_status, $allowed_statuses, true)) {
        updateOrderStatus($conn, $order_id, $new_status, 'admin', $notes);
        $message = "Order status updated successfully.";
    } else {
        $message = "Invalid input.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Order Status</title>
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
        input, select, textarea, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
        }
        button {
            margin-top: 18px;
            background: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #218838;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            background: #eef8ee;
            color: #1e6b2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Order Status</h2>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="order_id">Order ID</label>
            <input type="number" name="order_id" id="order_id" required>

            <label for="new_status">New Status</label>
            <select name="new_status" id="new_status" required>
                <option value="pending">pending</option>
                <option value="confirmed">confirmed</option>
                <option value="preparing">preparing</option>
                <option value="ready_for_pickup">ready_for_pickup</option>
                <option value="picked_up">picked_up</option>
                <option value="on_the_way">on_the_way</option>
                <option value="delivered">delivered</option>
                <option value="cancelled">cancelled</option>
            </select>

            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="4" placeholder="Optional notes..."></textarea>

            <button type="submit">Update Status</button>
        </form>
    </div>
</body>
</html>