<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BannerBackground;
use App\Models\Banner;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $productIds = Product::query()->pluck('id', 'product_code');

        $banners = [
            [
                'tag' => '2026 ROAD COLLECTION',
                'title' => "軽さは、\n遠くまで行くための道具だ。",
                'subtitle' => '新型カーボンフレーム ROADSTER RC7 登場',
                'product_code' => 'RC7-105',
                'background' => BannerBackground::Blue,
                'sort_order' => 0,
            ],
            [
                'tag' => 'E-BIKE / URBAN',
                'title' => "坂のない街に、\n住み替えなくていい。",
                'subtitle' => 'VOLT E-URBAN — 最大航続90km',
                'product_code' => 'EV-URB',
                'background' => BannerBackground::Rose,
                'sort_order' => 1,
            ],
            [
                'tag' => 'APPAREL 2026',
                'title' => "走るための、\n一枚。",
                'subtitle' => 'チームジャージ 2026 コレクション',
                'product_code' => 'AP-JRS26',
                'background' => BannerBackground::Coral,
                'sort_order' => 2,
            ],
        ];

        foreach ($banners as $banner) {
            // 投入済みのバナーにも商品の紐付けを行き渡らせるため、既存行も更新する
            Banner::query()->updateOrCreate(
                ['tag' => $banner['tag']],
                [
                    'title' => $banner['title'],
                    'subtitle' => $banner['subtitle'],
                    'product_id' => $productIds[$banner['product_code']] ?? null,
                    'background' => $banner['background'],
                    'link_url' => null,
                    'sort_order' => $banner['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
