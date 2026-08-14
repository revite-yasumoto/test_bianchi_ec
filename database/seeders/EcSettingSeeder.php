<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EcSetting;
use Illuminate\Database\Seeder;

class EcSettingSeeder extends Seeder
{
    /**
     * 単一行（id=1）のEC基本設定を投入する。
     */
    public function run(): void
    {
        EcSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'free_shipping_threshold' => 10000,
                'cod_fee' => 330,
                'bank_transfer_note' => "ご注文後5営業日以内に下記口座へお振込みください。\nBianchi銀行 デモ支店 普通 1234567 ビアンキデモ（カ",
            ]
        );
    }
}
