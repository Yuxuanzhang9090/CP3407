<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Tracking_Order/order_helpers.php';

final class TrackingHelpersTest extends TestCase
{
    public function testGetOrderStatusLabels(): void
    {
        $labels = getOrderStatusLabels();

        $this->assertArrayHasKey('pending', $labels);
        $this->assertEquals('Order Placed', $labels['pending']);
        $this->assertEquals('Delivered', $labels['delivered']);
    }

    public function testGetTrackingSteps(): void
    {
        $steps = getTrackingSteps();

        $this->assertEquals('pending', $steps[0]);
        $this->assertEquals('delivered', end($steps));
    }

    public function testGetStepIndex(): void
    {
        $this->assertEquals(0, getStepIndex('pending'));
        $this->assertEquals(5, getStepIndex('on_the_way'));
        $this->assertEquals(-1, getStepIndex('invalid_status'));
    }
}