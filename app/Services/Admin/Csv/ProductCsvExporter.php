<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Models\Product;
use App\Models\ProductVariant;
use Generator;

class ProductCsvExporter
{
    /**
     * @return array<int, string>
     */
    public function header(): array
    {
        return ProductCsvImporter::HEADER;
    }

    /**
     * SKUあり商品はバリエーションごとに1行へ展開する。
     *
     * @return Generator<int, array<int, string|int>>
     */
    public function rows(): Generator
    {
        // cursor() は eager load が効かないため、チャンク単位で読む lazy() を使う
        $products = Product::query()
            ->with(['category', 'variants.stock'])
            ->orderBy('id')
            ->lazy();

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                yield $this->toRow($product, $variant);
            }
        }
    }

    /**
     * @return array<int, string|int>
     */
    private function toRow(Product $product, ProductVariant $variant): array
    {
        return [
            $product->product_code,
            $product->name,
            $product->category->name,
            $product->price,
            $product->has_sku ? 'あり' : 'なし',
            $variant->branch_code ?? '',
            $variant->stock?->quantity ?? 0,
            $product->is_published ? '公開' : '非公開',
        ];
    }
}
