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
    <link rel="stylesheet" href="/CP3407-main/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="page-container">

    <!-- Back button -->
    <div style="margin: 20px 0;">
        <a href="categories.php">
            <i class="fa-solid fa-arrow-left"></i> Back to Browse Page
        </a>
    </div>

    <!-- Search box -->
    <div class="search-box" style="margin: 20px 0; text-align: center;">
        <form method="GET" action="">
            <input 
                type="text" 
                name="category" 
                placeholder="Enter category, e.g. Pizza" 
                value="<?php echo htmlspecialchars($search); ?>"
                required
            >
            <button type="submit">
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
</html>