<?php
declare(strict_types=1);

function filterRestaurantsByCategoryName(array $restaurants, string $search): array
{
    $search = strtolower(trim($search));

    return array_values(array_filter($restaurants, function ($restaurant) use ($search) {
        $categoryName = strtolower((string)($restaurant['category_name'] ?? ''));
        return str_contains($categoryName, $search);
    }));
}