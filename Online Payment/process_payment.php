<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $card_number = str_replace(' ', '', $_POST['card_number'] ?? "");
    $card_name = $_POST['card_name'] ?? "";
    $expiry = $_POST['expiry'] ?? "";
    $cvv = $_POST['cvv'] ?? "";
    $payment_method = $_POST['payment_method'] ?? "";

    // Validation
    if(empty($card_number) || empty($card_name) || empty($expiry) || empty($cvv)){
        die("Please fill in all payment details.");
    }

    if(strlen($card_number) != 16 || !is_numeric($card_number)){
        die("Invalid card number.");
    }

    if(strlen($cvv) != 3 || !is_numeric($cvv)){
        die("Invalid CVV.");
    }

    // Connect to database
    $conn = new mysqli("localhost", "root", "", "food_delivery");

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Example total price (later this should come from cart)
    $total_price = 13.00;

    // INSERT PAYMENT
    $sql = "INSERT INTO payments (card_name, payment_method, total_price)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $card_name, $payment_method, $total_price);

    if($stmt->execute()){
        header("Location: payment_confirmation.php");
        exit();
    } else {
        echo "Payment failed.";
    }

    $stmt->close();
    $conn->close();

} else {
    echo "Invalid access.";
}
?>