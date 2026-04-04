<?php
session_start();
require_once(__DIR__ . "/../config.php");

$restaurant_id = $_POST['restaurant_id'];
$rating = $_POST['rating'] ?? null;
$review = $_POST['review'] ?? "";
$app_base_path = rtrim($app_url ?? ('/' . basename(dirname(__DIR__))), '/');
$menu_url = $app_base_path . "/Browse_Restaurants/menu.php?id=" . urlencode((string)$restaurant_id);

/* Optional: get logged-in user */
$user = $_SESSION['sess_user'] ?? "guest";

/* Allow empty rating + review (optional feature) */
if (empty($rating) && empty(trim($review))) {
    header("Location: " . $menu_url);
    exit();
}

$sql = "INSERT INTO reviews (restaurant_id, user_email, rating, review)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isis", $restaurant_id, $user, $rating, $review);
$stmt->execute();

header("Location: " . $menu_url);
exit();
