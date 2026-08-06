<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EcSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EcSetting>
 */
class EcSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'free_shipping_threshold' => 10000,
            'cod_fee' => 330,
            'bank_transfer_note' => 'ご注文後5営業日以内に下記口座へお振込みください。',
        ];
    }
}
