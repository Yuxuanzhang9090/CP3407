<?php
session_start();

$id = $_GET['id'];
$action = $_GET['action'];

if (!isset($_SESSION['cart'][$id])) {
    header("Location: cart.php");
    exit();
}

switch ($action) {

    case 'increase':
        $_SESSION['cart'][$id]['quantity']++;
        break;

    case 'decrease':
        $_SESSION['cart'][$id]['quantity']--;

        if ($_SESSION['cart'][$id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
        break;

    case 'remove':
        unset($_SESSION['cart'][$id]);
        break;
}

header("Location: cart.php");
exit();
