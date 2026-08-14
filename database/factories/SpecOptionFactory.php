<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SpecOptionType;
use App\Models\SpecOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecOption>
 */
class SpecOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => SpecOptionType::Size,
            'name' => fake()->unique()->randomElement(['S', 'M', 'L', 'XL']),
            'sort_order' => 0,
        ];
    }
}
