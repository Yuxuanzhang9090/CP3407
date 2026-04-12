<?php
session_start();

$cart = $_SESSION['cart'] ?? [];
$restaurant_id = isset($_SESSION['restaurant_id']) ? (int)$_SESSION['restaurant_id'] : 0;

$total = 0.00;
$cart_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CP3407/registration & login/style.css">
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Your Cart 🛒</h2>

    <?php if (!empty($cart) && is_array($cart)): ?>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($cart as $id => $item): ?>
                        <?php
                        if (!is_array($item)) {
                            continue;
                        }

                        $name = $item['name'] ?? 'Unknown Item';
                        $price = isset($item['price']) ? (float)$item['price'] : 0.00;
                        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;

                        if ($quantity <= 0) {
                            continue;
                        }

                        $subtotal = $price * $quantity;
                        $total += $subtotal;
                        $cart_count += $quantity;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($name); ?></td>

                            <td>$<?php echo number_format($price, 2); ?></td>

                            <td>
                                <a href="update_cart.php?action=decrease&id=<?php echo (int)$id; ?>" class="btn btn-sm btn-outline-secondary">-</a>

                                <span class="mx-2"><?php echo $quantity; ?></span>

                                <a href="update_cart.php?action=increase&id=<?php echo (int)$id; ?>" class="btn btn-sm btn-outline-secondary">+</a>
                            </td>

                            <td>$<?php echo number_format($subtotal, 2); ?></td>

                            <td>
                                <a href="update_cart.php?action=remove&id=<?php echo (int)$id; ?>" class="btn btn-sm btn-danger">
                                    Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <h5>Total Items: <?php echo $cart_count; ?></h5>
            <h4>Total: $<?php echo number_format($total, 2); ?></h4>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <?php if ($restaurant_id > 0): ?>
                <a href="menu.php?restaurant_id=<?php echo $restaurant_id; ?>" class="btn btn-secondary">
                    ← Back to Menu
                </a>
            <?php else: ?>
                <a href="categories.php" class="btn btn-secondary">
                    ← Back to Restaurants
                </a>
            <?php endif; ?>

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