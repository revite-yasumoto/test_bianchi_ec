<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['product_code', 'name', 'category_id', 'price', 'description', 'has_sku', 'is_published'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_sku' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('sort_order', 0);
    }

    /**
     * @return HasMany<ProductSpec, $this>
     */
    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        // 編集画面のカラー・サイズの並びを作成順で安定させる
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    /**
     * 在庫合計の集計に使う。`withSum('stocks', 'quantity')` でバリエーションを跨いだ合計を1クエリで得る。
     *
     * @return HasManyThrough<Stock, ProductVariant, $this>
     */
    public function stocks(): HasManyThrough
    {
        return $this->hasManyThrough(Stock::class, ProductVariant::class);
    }

    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * 取扱対象のバリエーションのうち、在庫が1件以上あるかを判定する（在庫の二値表示用）。
     */
    public function hasStock(): bool
    {
        return $this->variants()
            ->where('is_available', true)
            ->whereHas('stock', fn ($query) => $query->where('quantity', '>', 0))
            ->exists();
    }
}
