<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'branch_code' => null,
            'sku_code' => null,
            'size_name' => null,
            'color_name' => null,
            'is_available' => true,
        ];
    }

    /**
     * SKUあり（サイズ・カラーを持つ）バリエーションの状態。
     */
    public function withSku(string $sizeName, string $colorName, string $branchCode): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_code' => $branchCode,
            'size_name' => $sizeName,
            'color_name' => $colorName,
        ])->afterMaking(function (ProductVariant $variant) {
            $variant->sku_code = $variant->product?->product_code
                ? $variant->product->product_code.'-'.$variant->branch_code
                : null;
        });
    }
}
