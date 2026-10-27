<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BannerBackground;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag' => fake()->word(),
            'title' => fake()->sentence(),
            'subtitle' => fake()->sentence(),
            'product_id' => null,
            'background' => BannerBackground::Blue,
            'link_url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
