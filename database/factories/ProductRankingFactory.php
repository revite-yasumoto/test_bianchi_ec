<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRanking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRanking>
 */
class ProductRankingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_year_month' => now()->subMonthNoOverflow()->format('Y-m'),
            'category_id' => null,
            'product_id' => Product::factory(),
            'rank_position' => 1,
            'aggregated_at' => now(),
        ];
    }
}
