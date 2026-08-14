<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpec>
 */
class ProductSpecFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'label' => fake()->word(),
            'value' => fake()->words(2, true),
            'sort_order' => 0,
        ];
    }
}
