<?php

declare(strict_types=1);

namespace App\Actions\Admin\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;

class SyncProductVariants
{
    /**
     * 送信されたバリエーションを正として `product_variants` と `stocks` を同期する。
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    public function __invoke(Product $product, array $variants): void
    {
        if ($product->has_sku) {
            $this->syncWithSku($product, $variants);

            return;
        }

        $this->syncWithoutSku($product, $variants);
    }

    /**
     * SKUなし商品はサイズ・カラーを持たないバリエーション1件に集約する。
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncWithoutSku(Product $product, array $variants): void
    {
        $input = $variants[array_key_first($variants)] ?? [];

        $product->variants()
            ->where(fn ($query) => $query->whereNotNull('size_name')->orWhereNotNull('color_name'))
            ->delete();

        $this->releaseSkuCodes($product);

        $variant = $product->variants()->updateOrCreate(
            ['size_name' => null, 'color_name' => null],
            [
                'branch_code' => null,
                'sku_code' => $product->product_code,
                'is_available' => true,
            ],
        );

        $this->saveStock($variant, (int) ($input['quantity'] ?? 0));
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncWithSku(Product $product, array $variants): void
    {
        $submittedKeys = [];

        foreach ($variants as $variant) {
            $submittedKeys[] = $this->keyOf(
                $this->stringOrNull($variant['size_name'] ?? null),
                $this->stringOrNull($variant['color_name'] ?? null),
            );
        }

        foreach ($product->variants()->get() as $existing) {
            if (! in_array($this->keyOf($existing->size_name, $existing->color_name), $submittedKeys, true)) {
                $existing->delete();
            }
        }

        $this->releaseSkuCodes($product);

        foreach ($variants as $input) {
            $isAvailable = filter_var($input['is_available'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $branchCode = $this->stringOrNull($input['branch_code'] ?? null);

            $variant = $product->variants()->updateOrCreate(
                [
                    'size_name' => $this->stringOrNull($input['size_name'] ?? null),
                    'color_name' => $this->stringOrNull($input['color_name'] ?? null),
                ],
                [
                    'branch_code' => $branchCode,
                    'sku_code' => $isAvailable && $branchCode !== null
                        ? $product->product_code.'-'.$branchCode
                        : null,
                    'is_available' => $isAvailable,
                ],
            );

            $this->saveStock($variant, $isAvailable ? (int) ($input['quantity'] ?? 0) : 0);
        }
    }

    /**
     * `sku_code` はテーブル全体で一意のため、商品IDの変更や枝番の入れ替えで
     * 更新の途中に旧値と新値が衝突しうる。更新前に一旦すべて解放しておく。
     */
    private function releaseSkuCodes(Product $product): void
    {
        $product->variants()->update(['sku_code' => null]);
    }

    private function saveStock(ProductVariant $variant, int $quantity): void
    {
        Stock::query()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            ['quantity' => $quantity],
        );
    }

    private function keyOf(?string $size, ?string $color): string
    {
        return ($color ?? '').'|'.($size ?? '');
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
