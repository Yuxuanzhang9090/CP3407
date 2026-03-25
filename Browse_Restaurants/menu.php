<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$restaurant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($restaurant_id <= 0) {
    die("Invalid restaurant ID");
}

/* Store current restaurant in session */
$_SESSION['restaurant_id'] = $restaurant_id;

/* Get restaurant details */
$sql_restaurant = "SELECT * FROM restaurants WHERE id = ?";
$stmt = $conn->prepare($sql_restaurant);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result_restaurant = $stmt->get_result();

if ($result_restaurant->num_rows === 0) {
    die("Restaurant not found.");
}

$restaurant = $result_restaurant->fetch_assoc();

/* Get menu items for this restaurant */
$sql_menu = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY menu_category, id";
$stmt_menu = $conn->prepare($sql_menu);
$stmt_menu->bind_param("i", $restaurant_id);
$stmt_menu->execute();
$result_menu = $stmt_menu->get_result();

/* Group items by category */
$menu = [];
while ($row = $result_menu->fetch_assoc()) {
    $menu[$row['menu_category']][] = $row;
}

$categories = array_keys($menu);

/* Read cart from session */
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

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="/CP3407/registration & login/style.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="menu-page">

    <!-- Banner -->
    <div class="menu-banner" style="background-image: url('<?php echo htmlspecialchars($restaurant['image'] ?? '../images/banner_food.jpg'); ?>');">
        <div class="menu-banner-overlay"></div>

        <!-- Cart icon scrolls to cart bar -->
        <a href="cart.php" class="floating-cart-link">
            <i class="fa-solid fa-cart-shopping"></i>
            <?php echo $cart_count; ?>
        </a>
    </div>

    <!-- Restaurant info -->
    <div class="menu-info-card">
        <div class="menu-header-flex">
            <div class="menu-logo">
              <?php echo mb_substr($restaurant['name'], 0, 2, 'UTF-8'); ?>
            </div>
        </div>

        <h1 class="menu-store-name">
            <?php echo htmlspecialchars($restaurant['name']); ?>
        </h1>

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

    <!-- Tabs -->
    <?php if (!empty($categories)) { ?>
        <div class="menu-tabs">
            <?php foreach ($categories as $index => $category) { ?>
                <a href="#cat-<?php echo $index; ?>" class="menu-tab">
                    <?php echo htmlspecialchars($category); ?>
                </a>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- Menu sections -->
    <div class="menu-sections">
        <?php if (!empty($menu)) { ?>
            <?php foreach ($menu as $category => $items): ?>
                <?php $catIndex = array_search($category, $categories); ?>

                <div class="menu-section" id="cat-<?php echo $catIndex; ?>">
                    <h2 class="menu-section-title">
                        <?php echo htmlspecialchars($category); ?>
                    </h2>

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

                                    <button class="add-cart-btn" data-id="<?php echo $item['id']; ?>">
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

    <!-- Cart bar -->
    <div class="cart-bar show" id="cartBar">
        <div class="cart-summary">
            <i class="fa-solid fa-bag-shopping"></i>
            <span id="cartCount"><?php echo $cart_count; ?></span> items |
            Total: <span id="cartTotal"><?php echo number_format($cart_total, 1); ?></span>
        </div>

        <form action="../Order_Placing/place_order.php" method="POST">
            <button type="submit" class="checkout-btn">Place Order</button>
        </form>
    </div>

    <!-- Rate and Reviews Section -->
    <div class="container mt-5">
        <h3>Rate & Review</h3>

        <form action="submit_review.php" method="POST">
            <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">

            <!-- Rating -->
            <div class="mb-3">
                <label class="form-label">Rating (optional)</label>
                <select name="rating" class="form-select">
                    <option value="">Select rating</option>
                    <option value="1">⭐ 1</option>
                    <option value="2">⭐⭐ 2</option>
                    <option value="3">⭐⭐⭐ 3</option>
                    <option value="4">⭐⭐⭐⭐ 4</option>
                    <option value="5">⭐⭐⭐⭐⭐ 5</option>
                </select>
            </div>

            <!-- Review -->
            <div class="mb-3">
                <label class="form-label">Review (optional)</label>
                <textarea name="review" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                Submit Review
            </button>
        </form>
    </div>


</div>

<script>
document.querySelectorAll('.add-cart-btn').forEach(button => {
    button.addEventListener('click', function () {
        const itemId = this.dataset.id;

        fetch('../Order_Placing/add_to_cart.php', {
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
                document.getElementById('cartTotal').textContent = data.cart_total;
                document.getElementById('cartBar').classList.add('show');
            } else {
                alert(data.message || 'Failed to add item.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error adding item.');
        });
    });
});
</script>

</body>
</html>