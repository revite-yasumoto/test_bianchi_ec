<?php

declare(strict_types=1);

namespace App\Services\Ranking;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\ProductRanking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * 前月の販売実績（数量ベース）から全体・カテゴリ別のランキングを集計し、`product_rankings` を入れ替える。
 */
class RankingAggregator
{
    /** 各ランキングで保存する順位数 */
    private const RANK_LIMIT = 10;

    /**
     * @return string 集計対象月（例: 2026-07）
     */
    public function aggregate(?CarbonImmutable $baseDate = null): string
    {
        $target = ($baseDate ?? CarbonImmutable::today())->startOfMonth()->subMonthNoOverflow();
        $yearMonth = $target->format('Y-m');

        $rows = $this->salesOf($target);
        $aggregatedAt = CarbonImmutable::now();

        $records = [
            ...$this->rank($rows, null, $yearMonth, $aggregatedAt),
            ...$this->rankByCategory($rows, $yearMonth, $aggregatedAt),
        ];

        DB::transaction(function () use ($yearMonth, $records): void {
            // 同じ対象月を再集計しても行が重複しないよう、入れ替える
            ProductRanking::query()->where('target_year_month', $yearMonth)->delete();

            if ($records !== []) {
                ProductRanking::query()->insert($records);
            }
        });

        return $yearMonth;
    }

    /**
     * 対象月に売れた商品の数量合計。キャンセル注文と、削除済み商品の明細は集計しない。
     *
     * @return array<int, object{product_id: int, category_id: int, total_quantity: int}>
     */
    private function salesOf(CarbonImmutable $target): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereNotNull('order_items.product_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('orders.ordered_at', [$target->startOfMonth(), $target->endOfMonth()->endOfDay()])
            // ONLY_FULL_GROUP_BY のため、集約しない列はすべて GROUP BY に載せる
            ->selectRaw('order_items.product_id, products.category_id, SUM(order_items.quantity) as total_quantity')
            ->groupBy('order_items.product_id', 'products.category_id')
            ->get()
            ->all();
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function rankByCategory(array $rows, string $yearMonth, CarbonImmutable $aggregatedAt): array
    {
        $categoryIds = array_values(array_unique(array_map(
            fn (object $row): int => (int) $row->category_id,
            $rows,
        )));

        $records = [];

        foreach ($categoryIds as $categoryId) {
            $records = [...$records, ...$this->rank($rows, $categoryId, $yearMonth, $aggregatedAt)];
        }

        return $records;
    }

    /**
     * 数量の降順で順位を付ける。同数のときは商品IDの昇順で安定させる。
     *
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function rank(array $rows, ?int $categoryId, string $yearMonth, CarbonImmutable $aggregatedAt): array
    {
        $target = $categoryId === null
            ? $rows
            : array_values(array_filter($rows, fn (object $row): bool => (int) $row->category_id === $categoryId));

        usort($target, function (object $a, object $b): int {
            return [(int) $b->total_quantity, (int) $a->product_id] <=> [(int) $a->total_quantity, (int) $b->product_id];
        });

        $records = [];

        foreach (array_slice($target, 0, self::RANK_LIMIT) as $index => $row) {
            $records[] = [
                'target_year_month' => $yearMonth,
                'category_id' => $categoryId,
                'product_id' => (int) $row->product_id,
                'rank_position' => $index + 1,
                'aggregated_at' => $aggregatedAt,
                'created_at' => $aggregatedAt,
                'updated_at' => $aggregatedAt,
            ];
        }

        return $records;
    }
}
