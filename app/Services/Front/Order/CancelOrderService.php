<?php

declare(strict_types=1);

namespace App\Services\Front\Order;

use App\Actions\Order\RestoreStockFromOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CancelOrderService
{
    public function __construct(private readonly RestoreStockFromOrder $restoreStock) {}

    /**
     * 会員によるキャンセル。ステータス更新・履歴記録・在庫戻しを1トランザクションで行う。
     * キャンセルできないステータスのときは何もせず false を返す。
     */
    public function cancel(Order $order): bool
    {
        if (! $order->status->isCancelableByCustomer()) {
            return false;
        }

        DB::transaction(function () use ($order): void {
            $from = $order->status;

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            // 会員による操作は admin_id を持たない
            $order->statusHistories()->create([
                'admin_id' => null,
                'from_status' => $from->value,
                'to_status' => OrderStatus::Cancelled->value,
                'changed_at' => now(),
            ]);

            ($this->restoreStock)($order);
        });

        return true;
    }
}
