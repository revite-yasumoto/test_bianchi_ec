<?php

declare(strict_types=1);

namespace App\Actions\Admin\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class BuildProductFilter
{
    /**
     * @param  array{q?: string|null, category_id?: int|null, has_sku?: string|null, price_min?: int|null, price_max?: int|null}  $filters
     * @return Builder<Product>
     */
    public function __invoke(array $filters): Builder
    {
        return Product::query()
            ->with('category')
            ->withSum('stocks', 'quantity')
            ->when(
                $filters['q'] ?? null,
                fn (Builder $query, string $keyword) => $query->where(
                    fn (Builder $inner) => $inner
                        ->where('name', 'like', '%'.$this->escapeLike($keyword).'%')
                        ->orWhere('product_code', 'like', '%'.$this->escapeLike($keyword).'%'),
                ),
            )
            ->when(
                $filters['category_id'] ?? null,
                fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId),
            )
            ->when(
                ($filters['has_sku'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('has_sku', ($filters['has_sku'] ?? '') === 'with'),
            )
            ->when(
                $filters['price_min'] ?? null,
                // price は整数列のため比較値も整数で渡す（文字列との比較で索引が効かなくなるのを避ける）
                fn (Builder $query, int $min) => $query->where('price', '>=', $min),
            )
            ->when(
                $filters['price_max'] ?? null,
                fn (Builder $query, int $max) => $query->where('price', '<=', $max),
            )
            ->orderByDesc('id');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
