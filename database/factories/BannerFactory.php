<?php

declare(strict_types=1);

namespace Database\Factories;

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
            'background' => 'linear-gradient(115deg,#2F6F86 0%,#274F60 60%,#1b3844 100%)',
            'link_url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
