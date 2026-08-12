<?php

declare(strict_types=1);

namespace App\Services\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * ダッシュボードの集計。金額・件数はすべて `orders` のスナップショット列のみから算出し、
 * `order_items` や `products` を参照しない。
 */
class DashboardSummaryService
{
    /** 売上推移グラフの表示日数（本日を含む） */
    private const CHART_DAYS = 7;

    private const LATEST_ORDER_LIMIT = 5;

    /**
     * @return array{
     *     today_sales: int,
     *     today_sales_note: string,
     *     month_sales: int,
     *     month_sales_note: string,
     *     new_order_count: int,
     *     awaiting_payment_count: int,
     * }
     */
    public function summary(): array
    {
        $today = CarbonImmutable::today();

        $todaySales = $this->salesOn($today);
        $yesterdaySales = $this->salesOn($today->subDay());

        return [
            'today_sales' => $todaySales,
            'today_sales_note' => $this->comparisonNote($todaySales, $yesterdaySales),
            'month_sales' => (int) $this->salesQuery()
                ->whereBetween('ordered_at', [$today->startOfMonth(), $today->endOfMonth()->endOfDay()])
                ->sum('total'),
            'month_sales_note' => sprintf('%d月1日〜%d日', $today->month, $today->day),
            'new_order_count' => Order::query()->whereDate('ordered_at', $today)->count(),
            'awaiting_payment_count' => Order::query()
                ->where('status', OrderStatus::AwaitingPayment->value)
                ->count(),
        ];
    }

    /**
     * 本日を含む直近7日間の日別売上。注文がない日は0で埋める。
     *
     * @return list<array{label: string, amount: int}>
     */
    public function salesChart(): array
    {
        $today = CarbonImmutable::today();
        $from = $today->subDays(self::CHART_DAYS - 1);

        $amounts = $this->salesQuery()
            ->selectRaw('DATE(ordered_at) as ordered_date, SUM(total) as amount')
            ->whereBetween('ordered_at', [$from->startOfDay(), $today->endOfDay()])
            ->groupBy('ordered_date')
            ->pluck('amount', 'ordered_date');

        $chart = [];

        for ($offset = 0; $offset < self::CHART_DAYS; $offset++) {
            $date = $from->addDays($offset);

            $chart[] = [
                'label' => $date->format('n/j'),
                'amount' => (int) ($amounts[$date->toDateString()] ?? 0),
            ];
        }

        return $chart;
    }

    /**
     * @return list<array{
     *     id: int,
     *     order_number: string,
     *     customer_name: string,
     *     total: int,
     *     status: string,
     *     status_label: string,
     *     status_tone: array{fg: string, bg: string},
     * }>
     */
    public function latestOrders(): array
    {
        return Order::query()
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->limit(self::LATEST_ORDER_LIMIT)
            ->get()
            ->map(function (Order $order): array {
                [$fg, $bg] = $order->status->color();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'total' => $order->total,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'status_tone' => ['fg' => $fg, 'bg' => $bg],
                ];
            })
            ->all();
    }

    private function salesOn(CarbonImmutable $date): int
    {
        return (int) $this->salesQuery()->whereDate('ordered_at', $date)->sum('total');
    }

    /**
     * 売上の集計対象（キャンセル注文を除く）。
     *
     * @return Builder<Order>
     */
    private function salesQuery(): Builder
    {
        return Order::query()->where('status', '!=', OrderStatus::Cancelled->value);
    }

    private function comparisonNote(int $todaySales, int $yesterdaySales): string
    {
        if ($yesterdaySales === 0) {
            return '前日実績なし';
        }

        return sprintf('前日比 %+d%%', (int) round(($todaySales - $yesterdaySales) / $yesterdaySales * 100));
    }
}
