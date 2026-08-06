<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = ['ロードバイク', 'MTB', 'シティ', 'eバイク', 'パーツ', 'アパレル'];

        foreach ($names as $index => $name) {
            Category::query()->firstOrCreate(['name' => $name], ['sort_order' => $index]);
        }
    }
}
