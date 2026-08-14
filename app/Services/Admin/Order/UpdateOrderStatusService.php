<?php

declare(strict_types=1);

namespace App\Services\Admin\Order;

use App\Actions\Order\RestoreStockFromOrder;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class UpdateOrderStatusService
{
    public function __construct(private readonly RestoreStockFromOrder $restoreStock) {}

    /**
     * ステータス更新・履歴記録・在庫戻しを1トランザクションで行う。
     */
    public function update(Order $order, OrderStatus $to, ?Admin $admin): void
    {
        DB::transaction(function () use ($order, $to, $admin): void {
            $from = $order->status;

            $order->update([
                'status' => $to,
                'cancelled_at' => $to === OrderStatus::Cancelled ? now() : $order->cancelled_at,
            ]);

            $order->statusHistories()->create([
                'admin_id' => $admin?->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_at' => now(),
            ]);

            if ($to === OrderStatus::Cancelled) {
                ($this->restoreStock)($order);
            }
        });
    }
}
