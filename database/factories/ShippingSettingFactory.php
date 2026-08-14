<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Prefecture;
use App\Models\ShippingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingSetting>
 */
class ShippingSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prefecture_id' => Prefecture::factory(),
            'fee' => 500,
            'delivery_days' => 3,
        ];
    }
}
