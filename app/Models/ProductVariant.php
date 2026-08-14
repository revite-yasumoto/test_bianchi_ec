<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['product_id', 'branch_code', 'sku_code', 'size_name', 'color_name', 'is_available'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasOne<Stock, $this>
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * 在庫が1以上あるかを判定する（在庫の二値表示用）。
     */
    public function inStock(): bool
    {
        return ($this->stock?->quantity ?? 0) > 0;
    }

    /**
     * カート・注文明細に出すバリエーション名（例: レッド / M）。
     */
    public function displayName(): string
    {
        // 「0」のようなサイズ名を落とさないよう、空判定ではなく未設定かどうかで絞る
        $parts = array_filter(
            [$this->color_name, $this->size_name],
            fn (?string $name): bool => $name !== null && $name !== '',
        );

        return $parts === [] ? '規格なし' : implode(' / ', $parts);
    }
}
