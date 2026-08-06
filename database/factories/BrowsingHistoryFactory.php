<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BrowsingHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrowsingHistory>
 */
class BrowsingHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'viewed_at' => now(),
        ];
    }
}
