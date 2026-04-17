<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/checkout_helpers.php';

final class CheckoutValidationTest extends TestCase
{
    public function testValidCheckoutInput(): void
    {
        $cart = [
            1 => ['id' => 1, 'name' => 'Burger', 'price' => 8.50, 'quantity' => 2]
        ];

        $errors = validateCheckoutInput($cart, 3, '91234567', '123 Clementi Road');
        $this->assertEmpty($errors);
    }

    public function testEmptyCartShouldFail(): void
    {
        $errors = validateCheckoutInput([], 3, '91234567', '123 Clementi Road');
        $this->assertContains('Your cart is empty.', $errors);
    }

    public function testInvalidRestaurantShouldFail(): void
    {
        $cart = [
            1 => ['id' => 1, 'name' => 'Burger', 'price' => 8.50, 'quantity' => 2]
        ];

        $errors = validateCheckoutInput($cart, 0, '91234567', '123 Clementi Road');
        $this->assertContains('Invalid restaurant.', $errors);
    }

    public function testMissingPhoneAndAddressShouldFail(): void
    {
        $cart = [
            1 => ['id' => 1, 'name' => 'Burger', 'price' => 8.50, 'quantity' => 2]
        ];

        $errors = validateCheckoutInput($cart, 3, '', '');
        $this->assertContains('Phone number is required.', $errors);
        $this->assertContains('Address is required.', $errors);
    }
}