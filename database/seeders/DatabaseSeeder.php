<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. 依存順に呼び出す。
     */
    public function run(): void
    {
        $this->call([
            PrefectureSeeder::class,
            ShippingSettingSeeder::class,
            EcSettingSeeder::class,
            CategorySeeder::class,
            SpecOptionSeeder::class,
            AdminSeeder::class,
            ProductSeeder::class,
            UserSeeder::class,
            OrderSeeder::class,
            NewsSeeder::class,
            NoticeSeeder::class,
            BannerSeeder::class,
        ]);
    }
}
