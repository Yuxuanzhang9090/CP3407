<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DATABASE CONFIG
$host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "food_delivery";

$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// STRIPE CONFIG
$stripe_secret_key = "";
$stripe_publishable_key = "";
$webhook_secret = "";