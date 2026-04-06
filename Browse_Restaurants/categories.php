<?php
session_start();

if (!isset($_SESSION['sess_user'])) {
    header("Location: /CP3407/registration%20&%20login/login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$categoriesSql = "SELECT * FROM categories ORDER BY id";
$categoriesResult = $conn->query($categoriesSql);

if (!$categoriesResult) {
    die("Categories query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Restaurants</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="/CP3407/registration%20%26%20login/style.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm px-4">
    
    <!-- Left: Logo -->
    <a class="navbar-brand brand-text" href="categories.php">
        NomNow
    </a>

    <!-- Right: Actions -->
    <div class="ms-auto d-flex gap-2">
        <a href="/CP3407/Order_Placing/order_history.php" class="btn btn-outline-secondary">
            Order History
        </a>
        <a href="/CP3407/registration%20&%20login/logout.php" class="btn btn-outline-danger">
            Logout
        </a>
    </div>

</nav>


<div class="page-container">

    <!-- Search entry -->
    <div class="text-center mb-4">
        <a href="search_restaurants.php" style="text-decoration: none;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Search category...</span>
        </a>
    </div>

    <!-- Category navigation -->
    <div class="cat-row">
        <?php
        $categoriesNavSql = "SELECT * FROM categories ORDER BY id";
        $categoriesNavResult = $conn->query($categoriesNavSql);

        if (!$categoriesNavResult) {
            die("Categories nav query failed: " . $conn->error);
        }

        while ($cat = $categoriesNavResult->fetch_assoc()) {
        ?>
            <a class="cat-item" href="#category-<?php echo $cat['id']; ?>">
                <div class="cat-avatar">
                    <img 
                        src="<?php echo htmlspecialchars($cat['img']); ?>" 
                        alt="<?php echo htmlspecialchars($cat['name']); ?>"
                    >
                </div>
                <div class="cat-name">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </div>
            </a>
        <?php } ?>
    </div>

    <!-- Category sections -->
    <?php while ($category = $categoriesResult->fetch_assoc()) { ?>
        
        <div class="category-section mb-3" id="category-<?php echo $category['id']; ?>">
            <h2 class="category-title mb-3"><?php echo htmlspecialchars($category['name']); ?></h2>
            <hr class="category-line">

            <div class="restaurant-row">
                <?php
                $categoryId = (int)$category['id'];
                $restaurantsSql = "SELECT * FROM restaurants WHERE category_id = $categoryId ORDER BY id";
                $restaurantsResult = $conn->query($restaurantsSql);

                if (!$restaurantsResult) {
                    die("Restaurants query failed: " . $conn->error);
                }

                if ($restaurantsResult->num_rows > 0) {
                    while ($restaurant = $restaurantsResult->fetch_assoc()) {
                ?>
                        <a href="menu.php?id=<?php echo $restaurant['id']; ?>" class="restaurant-link">
                            <div class="restaurant-card">
                                <h3 class="restaurant-name">
                                    <?php echo htmlspecialchars($restaurant['name']); ?>
                                </h3>

                                <p class="restaurant-address">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?php echo htmlspecialchars($restaurant['address']); ?>
                                </p>

                                <p class="restaurant-rating">
                                    <i class="fa-solid fa-star"></i>
                                    Rating: <?php echo htmlspecialchars($restaurant['rating']); ?>
                                </p>

                                <p class="restaurant-hours">
                                    <i class="fa-regular fa-clock"></i>
                                    <?php echo htmlspecialchars($restaurant['opening_hours']); ?>
                                </p>
                            </div>
                        </a>
                <?php
                    }
                } else {
                ?>
                    <p>No restaurants available in this category.</p>
                <?php } ?>
            </div>
        </div>

    <?php } ?>

</div>
<footer class="site-footer">
    <div class="footer-top">
        <div class="footer-brand">
            <h2>Food Delivery</h2>
            <p>
                Fast, fresh, and reliable food delivery service.
                Order your favourite meals anytime and track your order in real time.
            </p>
        </div>

        <div class="footer-links">
            <div class="footer-column">
                <h3>About</h3>
                <a href="#">About Us</a>
                <a href="#">Contact</a>
                <a href="#">FAQ</a>
            </div>

            <div class="footer-column">
                <h3>Customer</h3>
                <a href="/CP3407/Order_Placing/my_orders.php">My Orders</a>
                <a href="/CP3407/Order_Placing/track_order.php">Track Order</a>
                <a href="/CP3407/Browse_Restaurants/categories.php">Browse Food</a>
            </div>

            <div class="footer-column">
                <h3>Partner</h3>
                <a href="#">Join as Restaurant</a>
                <a href="#">Become a Rider</a>
                <a href="#">Business Support</a>
            </div>

            <div class="footer-column">
                <h3>Follow Us</h3>
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2026 Food Delivery System | CP3407 Project</p>
    </div>
</footer>

</body>
</html>

