<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/cart_helpers.php';

final class CartHelpersTest extends TestCase
{
    public function testCalculateCartSubtotal(): void
    {
        $cart = [
            1 => ['price' => 10.00, 'quantity' => 2],
            2 => ['price' => 5.50, 'quantity' => 3]
        ];

        $this->assertEquals(36.50, calculateCartSubtotal($cart));
    }

    public function testCalculateCartCount(): void
    {
        $cart = [
            1 => ['price' => 10.00, 'quantity' => 2],
            2 => ['price' => 5.50, 'quantity' => 3]
        ];

        $this->assertEquals(5, calculateCartCount($cart));
    }

    public function testCalculateCartTotal(): void
    {
        $cart = [
            1 => ['price' => 10.00, 'quantity' => 2]
        ];

        $this->assertEquals(25.49, calculateCartTotal($cart, 3.99, 1.50));
    }
}