<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $card_number = $_POST['card_number'] ?? "";
    $card_name = $_POST['card_name'] ?? "";
    $expiry = $_POST['expiry'] ?? "";
    $cvv = $_POST['cvv'] ?? "";
    $payment_method = $_POST['payment_method'] ?? "";

    if(empty($card_number) || empty($card_name) || empty($expiry) || empty($cvv)){
        die("Please fill in all payment details.");
    }

    if(strlen($card_number) != 16){
        die("Invalid card number.");
    }

    if(strlen($cvv) != 3){
        die("Invalid CVV.");
    }

    // CONNECT TO DATABASE
    $conn = new mysqli("localhost", "root", "", "food_delivery");

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Example total price (later this should come from cart)
    $total_price = 22.50;

    // INSERT PAYMENT
    $sql = "INSERT INTO payments (card_name, payment_method, total_price)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $card_name, $payment_method, $total_price);
    $stmt->execute();

    // REDIRECT AFTER SAVING
    header("Location: confirmation.php");
    exit();

} else {
    echo "Invalid access.";
}

?>
