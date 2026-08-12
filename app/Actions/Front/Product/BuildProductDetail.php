<?php

declare(strict_types=1);

namespace App\Actions\Front\Product;

use App\Enums\SpecOptionType;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\SpecOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * 商品詳細のPropsを組み立てる。
 * 在庫数はフロントへ渡さず、バリエーションごとの在庫有無（boolean）だけを返す。
 */
class BuildProductDetail
{
    /**
     * `category` / `images` / `specs` / `variants.stock` をロード済みの商品を渡すこと。
     *
     * @return array<string, mixed>
     */
    public function __invoke(Product $product): array
    {
        $variants = $product->variants;

        return [
            'id' => $product->id,
            'product_code' => $product->product_code,
            'name' => $product->name,
            'category_name' => $product->category->name,
            'price' => $product->price,
            'description' => $product->description,
            'has_sku' => $product->has_sku,
            'is_sold_out' => $variants->every(
                fn (ProductVariant $variant): bool => ! $variant->is_available || ! $variant->inStock()
            ),
            'images' => $product->images
                ->map(fn (ProductImage $image): array => [
                    'url' => Storage::disk('public')->url($image->path),
                    'sort_order' => $image->sort_order,
                ])
                ->all(),
            'specs' => $product->specs
                ->map(fn (ProductSpec $spec): array => [
                    'label' => $spec->label,
                    'value' => $spec->value,
                ])
                ->all(),
            'variants' => $variants
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'size_name' => $variant->size_name,
                    'color_name' => $variant->color_name,
                    'sku_code' => $variant->sku_code,
                    'is_available' => $variant->is_available,
                    'in_stock' => $variant->inStock(),
                ])
                ->all(),
            'sizes' => $this->optionNames($variants, 'size_name', SpecOptionType::Size),
            'colors' => $this->optionNames($variants, 'color_name', SpecOptionType::Color),
        ];
    }

    /**
     * 選択肢の並びを規格管理（`spec_options.sort_order`）に合わせる。
     * 規格管理から削除された値を持つ商品でも選択肢を落とさないよう、未登録の値は末尾へ回す。
     *
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<int, string>
     */
    private function optionNames(Collection $variants, string $column, SpecOptionType $type): array
    {
        $names = $variants->pluck($column)->filter()->unique()->values();

        if ($names->isEmpty()) {
            return [];
        }

        $order = SpecOption::query()
            ->where('type', $type->value)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->flip();

        return $names
            ->sortBy(fn (string $name): int => $order[$name] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }
}
