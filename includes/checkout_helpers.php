<?php
declare(strict_types=1);

function validateCheckoutInput(array $cart, int $restaurantId, string $phone, string $address): array
{
    $errors = [];

    if (empty($cart) || !is_array($cart)) {
        $errors[] = 'Your cart is empty.';
    }

    if ($restaurantId <= 0) {
        $errors[] = 'Invalid restaurant.';
    }

    if (trim($phone) === '') {
        $errors[] = 'Phone number is required.';
    }

    if (trim($address) === '') {
        $errors[] = 'Address is required.';
    }

    return $errors;
}