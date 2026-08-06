<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'tag' => '2026 ROAD COLLECTION',
                'title' => "軽さは、\n遠くまで行くための道具だ。",
                'subtitle' => '新型カーボンフレーム ROADSTER RC7 登場',
                'background' => 'linear-gradient(115deg,#2F6F86 0%,#274F60 60%,#1b3844 100%)',
                'sort_order' => 0,
            ],
            [
                'tag' => 'E-BIKE / URBAN',
                'title' => "坂のない街に、\n住み替えなくていい。",
                'subtitle' => 'VOLT E-URBAN — 最大航続90km',
                'background' => 'linear-gradient(115deg,#C25E86 0%,#8f3e60 60%,#5d2740 100%)',
                'sort_order' => 1,
            ],
            [
                'tag' => 'APPAREL 2026',
                'title' => "走るための、\n一枚。",
                'subtitle' => 'チームジャージ 2026 コレクション',
                'background' => 'linear-gradient(115deg,#E1664B 0%,#b64530 55%,#7d2d1e 100%)',
                'sort_order' => 2,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::query()->firstOrCreate(
                ['tag' => $banner['tag']],
                [
                    'title' => $banner['title'],
                    'subtitle' => $banner['subtitle'],
                    'background' => $banner['background'],
                    'link_url' => null,
                    'sort_order' => $banner['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
