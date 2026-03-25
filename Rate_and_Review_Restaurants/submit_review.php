<?php
session_start();

$conn = new mysqli("localhost", "root", "", "food_delivery");

$restaurant_id = $_POST['restaurant_id'];
$rating = $_POST['rating'] ?? null;
$review = $_POST['review'] ?? "";

/* Optional: get logged-in user */
$user = $_SESSION['sess_user'] ?? "guest";

/* Allow empty rating + review (optional feature) */
if (empty($rating) && empty(trim($review))) {
    header("Location: menu.php?id=$restaurant_id");
    exit();
}

$sql = "INSERT INTO reviews (restaurant_id, user_email, rating, review)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isis", $restaurant_id, $user, $rating, $review);
$stmt->execute();

header("Location: menu.php?id=$restaurant_id");
exit();
