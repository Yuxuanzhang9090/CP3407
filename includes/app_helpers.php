<?php
declare(strict_types=1);

function cp3407_money(float $amount): string
{
    return 'SGD ' . number_format($amount, 2);
}

function cp3407_label(string $value): string
{
    return ucwords(str_replace('_', ' ', trim($value)));
}

function cp3407_badge_class(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'paid', 'completed', 'delivered', 'confirmed', 'ready_for_pickup' => 'success',
        'pending', 'pending_payment', 'preparing', 'picked_up', 'on_the_way' => 'warning text-dark',
        'failed', 'cancelled', 'partial_failed' => 'danger',
        default => 'secondary',
    };
}