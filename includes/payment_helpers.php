<?php
declare(strict_types=1);

function isSuccessfulPaymentStatus(string $paymentStatus): bool
{
    return strtolower(trim($paymentStatus)) === 'paid';
}

function getOrderStatusAfterPayment(string $paymentStatus): string
{
    return isSuccessfulPaymentStatus($paymentStatus) ? 'paid' : 'pending';
}