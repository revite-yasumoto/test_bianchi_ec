<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Prefecture;
use App\Models\ShippingSetting;
use Illuminate\Database\Seeder;

class ShippingSettingSeeder extends Seeder
{
    /**
     * 北海道・沖縄県は 1,000円/該当日数、その他は 500円/3日を初期値とする。
     */
    public function run(): void
    {
        Prefecture::query()->each(function (Prefecture $prefecture): void {
            ShippingSetting::query()->firstOrCreate(
                ['prefecture_id' => $prefecture->id],
                [
                    'fee' => in_array($prefecture->name, ['北海道', '沖縄県'], true) ? 1000 : 500,
                    'delivery_days' => match ($prefecture->name) {
                        '北海道' => 5,
                        '沖縄県' => 6,
                        default => 3,
                    },
                ]
            );
        });
    }
}
