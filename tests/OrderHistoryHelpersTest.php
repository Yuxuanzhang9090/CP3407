<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Order_Placing/order_history_helpers.php';

final class OrderHistoryHelpersTest extends TestCase
{
    public function testFormatOrderStatus(): void
    {
        $this->assertEquals('Ready for Pickup', formatOrderStatus('ready_for_pickup'));
        $this->assertEquals('Delivered', formatOrderStatus('delivered'));
    }

    public function testFormatPaymentStatus(): void
    {
        $this->assertEquals('Paid', formatPaymentStatus('paid'));
        $this->assertEquals('Pending', formatPaymentStatus('pending'));
    }

    public function testGetStatusBadgeClass(): void
    {
        $this->assertEquals('status-delivered', getStatusBadgeClass('delivered'));
        $this->assertEquals('status-cancelled', getStatusBadgeClass('cancelled'));
        $this->assertEquals('status-pending', getStatusBadgeClass('unknown'));
    }

    public function testGetPaymentBadgeClass(): void
    {
        $this->assertEquals('payment-paid', getPaymentBadgeClass('paid'));
        $this->assertEquals('payment-failed', getPaymentBadgeClass('failed'));
        $this->assertEquals('payment-pending', getPaymentBadgeClass('pending'));
    }
}