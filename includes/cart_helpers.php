<?php
declare(strict_types=1);

function calculateCartSubtotal(array $cart): float
{
    $subtotal = 0.0;

    foreach ($cart as $item) {
        if (!is_array($item)) {
            continue;
        }

        $price = isset($item['price']) ? (float)$item['price'] : 0.0;
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;

        if ($quantity <= 0) {
            continue;
        }

        $subtotal += $price * $quantity;
    }

    return round($subtotal, 2);
}

function calculateCartCount(array $cart): int
{
    $count = 0;

    foreach ($cart as $item) {
        if (!is_array($item)) {
            continue;
        }

        $count += (int)($item['quantity'] ?? 0);
    }

    return $count;
}

function calculateCartTotal(array $cart, float $deliveryFee = 0.0, float $serviceFee = 0.0): float
{
    return round(calculateCartSubtotal($cart) + $deliveryFee + $serviceFee, 2);
}