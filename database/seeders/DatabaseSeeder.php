<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Ranking\RankingAggregator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. 依存順に呼び出す。
     */
    public function run(RankingAggregator $rankingAggregator): void
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

        // ランキングは注文の集計結果のため、注文の投入後にしか作れない
        $rankingAggregator->aggregate(CarbonImmutable::parse(OrderSeeder::RANKING_BASE_DATE));
    }
}
