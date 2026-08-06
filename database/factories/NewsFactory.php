<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NewsCategory;
use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'published_on' => now()->toDateString(),
            'category' => NewsCategory::NewProduct,
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'is_published' => true,
        ];
    }
}
