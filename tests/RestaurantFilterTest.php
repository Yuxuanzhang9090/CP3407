<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/restaurant_filter_helpers.php';

final class RestaurantFilterTest extends TestCase
{
    public function testFilterRestaurantsByCategoryName(): void
    {
        $restaurants = [
            ['name' => 'KFC', 'category_name' => 'Fast Food'],
            ['name' => 'Starbucks', 'category_name' => 'Drinks'],
            ['name' => 'McDonalds', 'category_name' => 'Fast Food']
        ];

        $result = filterRestaurantsByCategoryName($restaurants, 'Fast Food');

        $this->assertCount(2, $result);
        $this->assertEquals('KFC', $result[0]['name']);
        $this->assertEquals('McDonalds', $result[1]['name']);
    }

    public function testFilterRestaurantsByCategoryNameNoMatch(): void
    {
        $restaurants = [
            ['name' => 'KFC', 'category_name' => 'Fast Food']
        ];

        $result = filterRestaurantsByCategoryName($restaurants, 'Desserts');

        $this->assertCount(0, $result);
    }
}