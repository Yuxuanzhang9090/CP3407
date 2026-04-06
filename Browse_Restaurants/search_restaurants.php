<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = "";
$restaurants = [];
$matchedCategory = "";

if (isset($_GET['category']) && trim($_GET['category']) !== "") {
    $search = trim($_GET['category']);

    $sql = "SELECT r.*, c.name AS category_name
            FROM restaurants r
            INNER JOIN categories c ON r.category_id = c.id
            WHERE c.name LIKE ?
            ORDER BY r.id";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $searchParam = "%" . $search . "%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $restaurants[] = $row;
        }
        $matchedCategory = $restaurants[0]['category_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Restaurants</title>

    <!-- Bootstrp -->
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

    <!-- Right: Logout -->
    <div class="ms-auto">
        <a href="/CP3407/registration%20&%20login/logout.php" class="btn btn-outline-danger">
            Logout
        </a>
    </div>

</nav>

<div class="page-container">

    <!-- Back button -->
    <div style="margin: 20px 0;">
        <a href="categories.php">
            <i class="fa-solid fa-arrow-left"></i> Back to Browse Page
        </a>
    </div>

    <!-- Search box -->
    <div class="search-box" style="margin: 20px 0; text-align: center;">
        <form method="GET" action="" class="d-flex justify-content-center gap-2">
            <input 
                type="text" 
                name="category"
                class="form-control w-50" 
                placeholder="Enter category, e.g. Pizza" 
                value="<?php echo htmlspecialchars($search); ?>"
                required
            >
            <button type="submit" class="btn btn-primary">
                Search
            </button>
        </form>
    </div>

    <!-- Search result -->
    <?php if ($search !== ""): ?>
        <div style="margin: 20px 0;">
            <h2>Search results for: "<?php echo htmlspecialchars($search); ?>"</h2>
        </div>

        <?php if (!empty($restaurants)): ?>
            <div class="category-section">
                <h2 class="category-title"><?php echo htmlspecialchars($matchedCategory); ?></h2>
                <hr class="category-line">

                <div class="restaurant-row">
                    <?php foreach ($restaurants as $restaurant): ?>
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
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="margin: 30px 0; text-align: center;">
                <h3>No restaurants found.</h3>
                <p>Please try categories like Fast Food, Drinks, Chinese Food, Western Food, BBQ or Pizza.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

</body>
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
</html>