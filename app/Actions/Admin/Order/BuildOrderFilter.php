<?php

declare(strict_types=1);

namespace App\Actions\Admin\Order;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class BuildOrderFilter
{
    /**
     * 一覧は注文時点のスナップショット列だけで描画するため、users や products は結合しない。
     *
     * @param  array{status?: string|null, q?: string|null}  $filters
     * @return Builder<Order>
     */
    public function __invoke(array $filters): Builder
    {
        return Order::query()
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('status', $filters['status']),
            )
            ->when(
                $filters['q'] ?? null,
                fn (Builder $query, string $keyword) => $query->where(
                    fn (Builder $inner) => $inner
                        ->where('order_number', 'like', '%'.$this->escapeLike($keyword).'%')
                        ->orWhere('customer_name', 'like', '%'.$this->escapeLike($keyword).'%'),
                ),
            )
            ->orderByDesc('ordered_at')
            ->orderByDesc('id');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
