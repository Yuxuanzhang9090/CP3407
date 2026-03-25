<?php
session_start();

$cart = $_SESSION['cart'] ?? [];

$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/CP3407/registration & login/style.css">
</head>

<body>

<div class="container mt-5">
    <h2 class="mb-4">Your Cart 🛒</h2>

    <?php if (!empty($cart)): ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($cart as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['price']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Total: <?php echo number_format($total, 2); ?></h4>

        <div class="mt-3">
            <a href="menu.php?id=<?php echo $_SESSION['restaurant_id']; ?>" class="btn btn-secondary">
                ← Back to Menu
            </a>

            <a href="../Order_Placing/place_order.php" class="btn btn-success">
                Proceed to Payment
            </a>
        </div>

    <?php else: ?>
        <p>Your cart is empty.</p>

        <a href="categories.php" class="btn btn-primary">
            Browse Food
        </a>
    <?php endif; ?>
</div>

</body>
</html>
