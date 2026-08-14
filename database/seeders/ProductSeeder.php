<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * モックHTMLの商品9件（SKUなし6件・あり3件）を投入する。
     */
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');

        $simpleProducts = [
            ['code' => 'RC7-105', 'name' => 'ROADSTER RC7 / 105', 'cat' => 'ロードバイク', 'price' => 398000, 'stock' => 4],
            ['code' => 'RC5-TGR', 'name' => 'ROADSTER RC5 / TIAGRA', 'cat' => 'ロードバイク', 'price' => 268000, 'stock' => 0],
            ['code' => 'MT3-STD', 'name' => 'TRAILHEAD MT3', 'cat' => 'MTB', 'price' => 198000, 'stock' => 7],
            ['code' => 'C1-CTY', 'name' => 'CITYLINE C1', 'cat' => 'シティ', 'price' => 98000, 'stock' => 15],
            ['code' => 'EV-URB', 'name' => 'VOLT E-URBAN', 'cat' => 'eバイク', 'price' => 328000, 'stock' => 3],
            ['code' => 'PT-CGE', 'name' => 'カーボンボトルケージ', 'cat' => 'パーツ', 'price' => 5800, 'stock' => 32],
        ];

        foreach ($simpleProducts as $data) {
            $product = Product::query()->firstOrCreate(
                ['product_code' => $data['code']],
                [
                    'name' => $data['name'],
                    'category_id' => $categories[$data['cat']],
                    'price' => $data['price'],
                    'description' => $data['name'].'の商品説明（デモ用ダミーテキスト）。',
                    'has_sku' => false,
                    'is_published' => true,
                ]
            );

            $variant = ProductVariant::query()->firstOrCreate(
                ['product_id' => $product->id, 'size_name' => null, 'color_name' => null],
                ['branch_code' => null, 'sku_code' => $product->product_code, 'is_available' => true]
            );

            Stock::query()->firstOrCreate(
                ['product_variant_id' => $variant->id],
                ['quantity' => $data['stock']]
            );
        }

        $skuProducts = [
            [
                'code' => 'AP-JRS26', 'name' => 'チームジャージ 2026', 'cat' => 'アパレル', 'price' => 14800,
                'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['アクア', 'ブラック', 'ホワイト'],
                'out' => ['XL|ホワイト', 'S|ブラック', 'XL|アクア'],
                'stock' => 8,
            ],
            [
                'code' => 'PT-BTL6', 'name' => 'サーマルボトル 600ml', 'cat' => 'パーツ', 'price' => 3200,
                'sizes' => ['600ml'], 'colors' => ['アクア', 'ブラック'],
                'out' => [],
                'stock' => 20,
            ],
            [
                'code' => 'AP-GLV', 'name' => 'グローブ PRO', 'cat' => 'アパレル', 'price' => 6800,
                'sizes' => ['S', 'M', 'L'], 'colors' => ['ブラック', 'ホワイト'],
                'out' => ['S|ホワイト'],
                'stock' => 6,
            ],
        ];

        foreach ($skuProducts as $data) {
            $product = Product::query()->firstOrCreate(
                ['product_code' => $data['code']],
                [
                    'name' => $data['name'],
                    'category_id' => $categories[$data['cat']],
                    'price' => $data['price'],
                    'description' => $data['name'].'の商品説明（デモ用ダミーテキスト）。',
                    'has_sku' => true,
                    'is_published' => true,
                ]
            );

            $branch = 0;

            foreach ($data['colors'] as $colorIndex => $color) {
                foreach ($data['sizes'] as $sizeIndex => $size) {
                    $branch++;
                    $branchCode = (string) ((($colorIndex + 1) * 10) + $sizeIndex + 1);
                    $isAvailable = ! in_array($size.'|'.$color, $data['out'], true);

                    $variant = ProductVariant::query()->firstOrCreate(
                        ['product_id' => $product->id, 'size_name' => $size, 'color_name' => $color],
                        [
                            'branch_code' => $branchCode,
                            'sku_code' => $isAvailable ? $product->product_code.'-'.$branchCode : null,
                            'is_available' => $isAvailable,
                        ]
                    );

                    Stock::query()->firstOrCreate(
                        ['product_variant_id' => $variant->id],
                        ['quantity' => $isAvailable ? $data['stock'] : 0]
                    );
                }
            }
        }

        $this->seedSpecs();
    }

    /**
     * 代表的な商品にスペック表を投入する（デモ用の一例のみ）。
     */
    private function seedSpecs(): void
    {
        $product = Product::query()->where('product_code', 'RC7-105')->first();

        if ($product === null || $product->specs()->exists()) {
            return;
        }

        $specs = [
            ['フレーム', 'カーボンモノコック'],
            ['フォーク', 'フルカーボン'],
            ['コンポーネント', '2×12速 油圧ディスク'],
            ['ホイール', 'アルミクリンチャー 700C'],
            ['サイズ展開', '470 / 500 / 530 / 550'],
            ['重量', '8.2kg（530mm）'],
        ];

        foreach ($specs as $index => [$label, $value]) {
            ProductSpec::query()->create([
                'product_id' => $product->id,
                'label' => $label,
                'value' => $value,
                'sort_order' => $index,
            ]);
        }
    }
}
