<?php
declare(strict_types=1);

function isValidStatusTransition(string $currentStatus, string $nextStatus): bool
{
    $allowedTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready_for_pickup', 'cancelled'],
        'ready_for_pickup' => ['picked_up'],
        'picked_up' => ['on_the_way'],
        'on_the_way' => ['delivered'],
        'delivered' => [],
        'cancelled' => []
    ];

    return in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true);
}