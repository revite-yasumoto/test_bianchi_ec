<?php

declare(strict_types=1);

namespace App\Actions\Admin\Stock;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Builder;

class BuildStockFilter
{
    /**
     * 在庫はバリエーション単位のため、商品の属性で絞り込む・並べ替えるには結合が要る。
     *
     * @param  array{has_sku?: string|null, category_id?: int|null, q?: string|null}  $filters
     * @return Builder<Stock>
     */
    public function __invoke(array $filters): Builder
    {
        return Stock::query()
            ->select('stocks.*')
            ->join('product_variants', 'product_variants.id', '=', 'stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->with(['variant.product.category'])
            ->when(
                ($filters['has_sku'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('products.has_sku', ($filters['has_sku'] ?? '') === 'with'),
            )
            ->when(
                $filters['category_id'] ?? null,
                fn (Builder $query, int $categoryId) => $query->where('products.category_id', $categoryId),
            )
            ->when(
                $filters['q'] ?? null,
                fn (Builder $query, string $keyword) => $query->where(
                    'products.name',
                    'like',
                    '%'.$this->escapeLike($keyword).'%',
                ),
            )
            ->orderBy('products.name')
            ->orderBy('product_variants.color_name')
            ->orderBy('product_variants.size_name')
            ->orderBy('stocks.id');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
