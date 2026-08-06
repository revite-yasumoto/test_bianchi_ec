<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'display_start_on' => now()->subDay()->toDateString(),
            'display_end_on' => now()->addDays(7)->toDateString(),
        ];
    }
}
