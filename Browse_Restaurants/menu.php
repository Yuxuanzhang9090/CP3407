<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$restaurant_id = 0;

if (isset($_GET['restaurant_id'])) {
    $restaurant_id = (int)$_GET['restaurant_id'];
} elseif (isset($_GET['id'])) {
    $restaurant_id = (int)$_GET['id'];
}

if ($restaurant_id <= 0) {
    die("Invalid restaurant ID");
}

/* Store current restaurant in session */
$_SESSION['restaurant_id'] = $restaurant_id;

/* Get restaurant details */
$sql_restaurant = "SELECT * FROM restaurants WHERE id = ?";
$stmt = $conn->prepare($sql_restaurant);

if (!$stmt) {
    die("Prepare failed (restaurant): " . $conn->error);
}

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

if (!$stmt_menu) {
    die("Prepare failed (menu): " . $conn->error);
}

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
    if (is_array($cart_item)) {
        $cart_count += (int)($cart_item['quantity'] ?? 0);
        $cart_total += (float)($cart_item['price'] ?? 0) * (int)($cart_item['quantity'] ?? 0);
    }
}

function resolveMenuItemImage(string $storedPath, string $category): string
{
    $projectRoot = dirname(__DIR__);
    $normalizedPath = trim(str_replace("\\", "/", $storedPath));

    if ($normalizedPath !== '') {
        $relativePath = preg_replace('#^\.\./#', '', $normalizedPath);
        $absolutePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativePath);

        if (is_file($absolutePath)) {
            return "/CP3407/" . ltrim($relativePath, "/");
        }
    }

    $fallbackByCategory = [
        'Burgers' => '/CP3407/images/fast_food.png',
        'Sides' => '/CP3407/images/fast_food.png',
        'Drinks' => '/CP3407/images/drinks.png',
        'Desserts' => '/CP3407/images/western_food.png',
    ];

    return $fallbackByCategory[$category] ?? '/CP3407/images/fast_food.png';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> Menu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CP3407/registration%20%26%20login/style.css?v=<?php echo file_exists(__DIR__ . '/../registration & login/style.css') ? filemtime(__DIR__ . '/../registration & login/style.css') : time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="menu-page">

    <div class="menu-banner" style="background-image: url('<?php echo htmlspecialchars($restaurant['image'] ?? '../images/banner_food.jpg'); ?>');">
        <div class="menu-banner-overlay"></div>

        <a href="cart.php" class="floating-cart-link">
            <i class="fa-solid fa-cart-shopping"></i>
            <?php echo $cart_count; ?>
        </a>
    </div>

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

    <?php if (!empty($categories)) { ?>
        <div class="menu-tabs">
            <?php foreach ($categories as $index => $category) { ?>
                <a href="#cat-<?php echo $index; ?>" class="menu-tab">
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
                    <h2 class="menu-section-title">
                        <?php echo htmlspecialchars($category); ?>
                    </h2>

                    <div class="menu-grid-phone">
                        <?php foreach ($items as $item): ?>
                            <?php $itemImage = resolveMenuItemImage((string)($item['image'] ?? ''), (string)($item['menu_category'] ?? '')); ?>
                            <div class="dish-card">
                                <div class="dish-image-wrap">
                                    <img src="<?php echo htmlspecialchars($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                                </div>

                                <div class="dish-name">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>

                                <div class="dish-desc">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </div>

                                <div class="dish-price-row">
                                    <div class="dish-price">
                                        <?php echo number_format((float)$item['price'], 1); ?>
                                    </div>

                                    <button class="add-cart-btn" data-id="<?php echo (int)$item['id']; ?>">
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

    <div class="container mt-5">
        <h3>Rate & Review</h3>

        <form action="/CP3407/Rate_and_Review_Restaurants/submit_review.php" method="POST">
            <input type="hidden" name="restaurant_id" value="<?php echo (int)$restaurant_id; ?>">

            <div class="mb-3">
                <label class="form-label">Rating (optional)</label>
                <select name="rating" class="form-select">
                    <option value="">Select rating</option>
                    <option value="1">⭐</option>
                    <option value="2">⭐⭐</option>
                    <option value="3">⭐⭐⭐</option>
                    <option value="4">⭐⭐⭐⭐</option>
                    <option value="5">⭐⭐⭐⭐⭐</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Review (optional)</label>
                <textarea name="review" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                Submit Review
            </button>
        </form>

        <?php
        $sql_reviews = "SELECT * FROM reviews WHERE restaurant_id = ? ORDER BY created_at DESC";
        $stmt_reviews = $conn->prepare($sql_reviews);

        if (!$stmt_reviews) {
            die("Prepare failed (reviews): " . $conn->error);
        }

        $stmt_reviews->bind_param("i", $restaurant_id);
        $stmt_reviews->execute();
        $result_reviews = $stmt_reviews->get_result();
        ?>

        <div class="container mt-4">
            <div class="customer-reviews-section mt-4">
                <h3 class="section-title mb-3">Customer Reviews</h3>

                <?php
                $reviewSql = "SELECT user_email, rating, review, created_at 
                              FROM reviews 
                              WHERE restaurant_id = ? 
                              ORDER BY created_at DESC";

                $stmtReviewList = $conn->prepare($reviewSql);

                if (!$stmtReviewList) {
                    die("Prepare failed (review list): " . $conn->error);
                }

                $stmtReviewList->bind_param("i", $restaurant_id);
                $stmtReviewList->execute();
                $reviewResult = $stmtReviewList->get_result();

                if ($reviewResult->num_rows > 0):
                    while ($row = $reviewResult->fetch_assoc()):
                ?>
                    <div class="review-card mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="user-id-text"><?php echo htmlspecialchars($row['user_email']); ?></strong>
                            <span class="review-date text-muted small"><?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                        </div>

                        <div class="review-stars my-1">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= (int)$row['rating']) {
                                    echo '<i class="fa-solid fa-star text-warning"></i>';
                                } else {
                                    echo '<i class="fa-regular fa-star text-secondary"></i>';
                                }
                            }
                            ?>
                        </div>

                        <p class="review-text mt-2 mb-0">
                            <?php echo nl2br(htmlspecialchars($row['review'])); ?>
                        </p>
                    </div>
                <?php
                    endwhile;
                else:
                    echo '<p class="text-muted">No reviews yet. Be the first to leave one!</p>';
                endif;
                ?>
            </div>
        </div>
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