<?php
if (!function_exists('formatOrderStatus')) {
    function formatOrderStatus($status) {
        $map = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready_for_pickup' => 'Ready for Pickup',
            'picked_up' => 'Picked Up',
            'on_the_way' => 'On the Way',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled'
        ];

        return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('formatPaymentStatus')) {
    function formatPaymentStatus($status) {
        $map = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded'
        ];

        return $map[$status] ?? ucfirst($status);
    }
}

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch ($status) {
            case 'delivered':
                return 'status-delivered';
            case 'cancelled':
                return 'status-cancelled';
            case 'on_the_way':
            case 'picked_up':
                return 'status-progress';
            case 'confirmed':
            case 'preparing':
            case 'ready_for_pickup':
                return 'status-preparing';
            default:
                return 'status-pending';
        }
    }
}

if (!function_exists('getPaymentBadgeClass')) {
    function getPaymentBadgeClass($status) {
        switch ($status) {
            case 'paid':
                return 'payment-paid';
            case 'failed':
                return 'payment-failed';
            case 'refunded':
                return 'payment-refunded';
            default:
                return 'payment-pending';
        }
    }
}
?>