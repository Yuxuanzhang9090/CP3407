<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/order_transition_helpers.php';

final class OrderTransitionTest extends TestCase
{
    public function testValidStatusTransition(): void
    {
        $this->assertTrue(isValidStatusTransition('pending', 'confirmed'));
        $this->assertTrue(isValidStatusTransition('preparing', 'ready_for_pickup'));
        $this->assertTrue(isValidStatusTransition('on_the_way', 'delivered'));
    }

    public function testInvalidStatusTransition(): void
    {
        $this->assertFalse(isValidStatusTransition('pending', 'delivered'));
        $this->assertFalse(isValidStatusTransition('delivered', 'preparing'));
        $this->assertFalse(isValidStatusTransition('cancelled', 'confirmed'));
    }
}