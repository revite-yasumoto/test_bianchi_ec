<?php

declare(strict_types=1);

namespace App\Actions\Front\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * 商品カード（一覧・TOPのランキング／おすすめ／閲覧履歴）の表示データを組み立てる。
 * 在庫は二値のみを返し、在庫数はフロントへ渡さない。
 */
class BuildProductCard
{
    /**
     * カードの描画に必要なリレーションと在庫の集計を、商品の件数によらず一定のクエリ数で付与する。
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scope(Builder $query): Builder
    {
        return $query
            ->with(['category', 'mainImage'])
            ->withCount(['variants as in_stock_variants_count' => fn (Builder $variants) => $variants
                ->where('is_available', true)
                ->whereHas('stock', fn (Builder $stock) => $stock->where('quantity', '>', 0)),
            ]);
    }

    /**
     * @return array{id: int, name: string, category_name: string, product_code: string, price: int, main_image_url: string|null, is_sold_out: bool}
     */
    public function __invoke(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category_name' => $product->category->name,
            'product_code' => $product->product_code,
            'price' => $product->price,
            'main_image_url' => $product->mainImage
                ? Storage::disk('public')->url($product->mainImage->path)
                : null,
            'is_sold_out' => ((int) $product->in_stock_variants_count) === 0,
        ];
    }
}
