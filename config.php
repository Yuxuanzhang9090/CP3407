<?php
//DATABASE CONFIG
$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

//STRIPE CONFIG

// Secret key
$stripe_secret_key = getenv('STRIPE_SECRET_KEY');
// Publishable key
$stripe_publishable_key = getenv('STRIPE_PUBLISHABLE_KEY');
// Webhook secret
$webhook_secret = getenv('STRIPE_WEBHOOK_SECRET');