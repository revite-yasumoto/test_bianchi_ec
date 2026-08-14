<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SpecOptionType;
use App\Models\SpecOption;
use Illuminate\Database\Seeder;

class SpecOptionSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = ['S', 'M', 'L', 'XL', '600ml'];
        $colors = ['アクア', 'ブラック', 'ホワイト', 'レッド'];

        foreach ($sizes as $index => $name) {
            SpecOption::query()->firstOrCreate(
                ['type' => SpecOptionType::Size->value, 'name' => $name],
                ['sort_order' => $index]
            );
        }

        foreach ($colors as $index => $name) {
            SpecOption::query()->firstOrCreate(
                ['type' => SpecOptionType::Color->value, 'name' => $name],
                ['sort_order' => $index]
            );
        }
    }
}
