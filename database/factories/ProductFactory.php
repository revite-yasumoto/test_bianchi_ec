<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_code' => fake()->unique()->bothify('PC-####'),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'price' => fake()->numberBetween(1000, 400000),
            'description' => fake()->paragraph(),
            'has_sku' => false,
            'is_published' => true,
        ];
    }
}
