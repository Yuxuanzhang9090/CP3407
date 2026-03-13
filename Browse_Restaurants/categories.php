<?php
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="page-container">

    <!-- Search entry -->
    <div class="search-entry" style="margin: 20px 0; text-align: center;">
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
        
        <div class="category-section" id="category-<?php echo $category['id']; ?>">
            <h2 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
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

</body>
</html>

