<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$restaurant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$_SESSION['restaurant_id'] = $restaurant_id;

if ($restaurant_id <= 0) {
    die("Invalid restaurant ID");
}

$sql_restaurant = "SELECT * FROM restaurants WHERE id = ?";
$stmt = $conn->prepare($sql_restaurant);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result_restaurant = $stmt->get_result();

if ($result_restaurant->num_rows === 0) {
    die("Restaurant not found.");
}

$restaurant = $result_restaurant->fetch_assoc();

$sql_menu = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY menu_category, id";
$stmt_menu = $conn->prepare($sql_menu);
$stmt_menu->bind_param("i", $restaurant_id);
$stmt_menu->execute();
$result_menu = $stmt_menu->get_result();

$menu = [];
while ($row = $result_menu->fetch_assoc()) {
    $menu[$row['menu_category']][] = $row;
}

$categories = array_keys($menu);

$cart = $_SESSION['cart'] ?? [];
$cart_count = 0;
$cart_total = 0;

foreach ($cart as $cart_item) {
    $cart_count += $cart_item['quantity'];
    $cart_total += $cart_item['price'] * $cart_item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> Menu</title>

    <link rel="stylesheet" href="/CP3407-main/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="menu-page">

    <div class="menu-banner" style="background-image: url('<?php echo htmlspecialchars($restaurant['image'] ?? '../images/banner_food.jpg'); ?>');">
        <div class="menu-banner-overlay"></div>

        <a href="checkout.php" class="menu-cart-icon">
            <i class="fa-solid fa-cart-shopping"></i>
        </a>
    </div>

    <div class="menu-info-card">
        <div class="menu-logo">
            <?php
            $firstChar = mb_substr($restaurant['name'], 0, 2, 'UTF-8');
            echo htmlspecialchars($firstChar);
            ?>
        </div>

        <h1 class="menu-store-name"><?php echo htmlspecialchars($restaurant['name']); ?></h1>

        <p class="menu-store-subtitle">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($restaurant['address']); ?>
        </p>

        <div class="menu-store-meta">
            <div><i class="fa-solid fa-motorcycle"></i> free delivery</div>
            <div><i class="fa-regular fa-clock"></i> 30 minutes</div>
            <div><i class="fa-solid fa-star"></i> rate <?php echo htmlspecialchars($restaurant['rating']); ?></div>
        </div>
    </div>

    <?php if (!empty($categories)) { ?>
        <div class="menu-tabs">
            <?php foreach ($categories as $index => $category) { ?>
                <a href="#cat-<?php echo $index; ?>" class="menu-tab <?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($category); ?>
                </a>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="menu-sections">
        <?php if (!empty($menu)) { ?>
            <?php foreach ($menu as $category => $items): ?>
                <?php $catIndex = array_search($category, $categories); ?>
                <div class="menu-section" id="cat-<?php echo $catIndex; ?>">
                    <h2 class="menu-section-title"><?php echo htmlspecialchars($category); ?></h2>

                    <div class="menu-grid-phone">
                        <?php foreach ($items as $item): ?>
                            <div class="dish-card">
                                <div class="dish-image-wrap">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>

                                <div class="dish-name">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>

                                <div class="dish-desc">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </div>

                                <div class="dish-price-row">
                                    <div class="dish-price">
                                         <?php echo number_format($item['price'], 1); ?>
                                    </div>

                                    <button class="add-cart-btn"
                                            data-id="<?php echo $item['id']; ?>"
                                            data-price="<?php echo $item['price']; ?>">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php } else { ?>
            <p class="menu-empty">No menu items found for this restaurant.</p>
        <?php } ?>
    </div>

    <div class="cart-bar <?php echo $cart_count > 0 ? 'show' : ''; ?>" id="cartBar">
        <div class="cart-summary">
            <i class="fa-solid fa-bag-shopping"></i>
            <span id="cartCount"><?php echo $cart_count; ?></span> items |
            Total: <span id="cartTotal"><?php echo number_format($cart_total, 1); ?></span>
        </div>

        <form action="place_order.php" method="POST">
            <button type="submit" class="checkout-btn">Place Order</button>
        </form>
    </div>

</div>

<script>
document.querySelectorAll('.add-cart-btn').forEach(button => {
    button.addEventListener('click', function () {
        const itemId = this.dataset.id;

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'item_id=' + encodeURIComponent(itemId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cartCount').textContent = data.cart_count;
                document.getElementById('cartTotal').textContent = data.cart_total.toFixed(1);
                document.getElementById('cartBar').classList.add('show');
            } else {
                alert(data.message || 'Failed to add item.');
            }
        })
        .catch(error => {
            console.error(error);
            alert('Error adding item.');
        });
    });
});

</script>

</body>
</html>