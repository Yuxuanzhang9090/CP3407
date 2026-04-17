<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/payment_helpers.php';

final class PaymentHelpersTest extends TestCase
{
    public function testPaidStatusShouldBeSuccessful(): void
    {
        $this->assertTrue(isSuccessfulPaymentStatus('paid'));
        $this->assertEquals('paid', getOrderStatusAfterPayment('paid'));
    }

    public function testFailedStatusShouldRemainPending(): void
    {
        $this->assertFalse(isSuccessfulPaymentStatus('failed'));
        $this->assertEquals('pending', getOrderStatusAfterPayment('failed'));
    }

    public function testCancelledStatusShouldRemainPending(): void
    {
        $this->assertFalse(isSuccessfulPaymentStatus('cancelled'));
        $this->assertEquals('pending', getOrderStatusAfterPayment('cancelled'));
    }
}