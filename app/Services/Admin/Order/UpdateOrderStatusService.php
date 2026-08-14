<?php

declare(strict_types=1);

namespace App\Services\Admin\Order;

use App\Actions\Order\RestoreStockFromOrder;
use App\Enums\OrderStatus;
use App\Mail\Front\OrderShipped;
use App\Models\Admin;
use App\Models\Order;
use App\Services\Mail\NotificationMailer;
use Illuminate\Support\Facades\DB;

class UpdateOrderStatusService
{
    public function __construct(
        private readonly RestoreStockFromOrder $restoreStock,
        private readonly NotificationMailer $notificationMailer,
    ) {}

    /**
     * ステータス更新・履歴記録・在庫戻しを1トランザクションで行い、出荷の通知はその外で送る。
     */
    public function update(
        Order $order,
        OrderStatus $to,
        ?Admin $admin,
        ?string $trackingNumber = null,
        bool $notifiesCustomer = false,
    ): void {
        DB::transaction(function () use ($order, $to, $admin, $trackingNumber): void {
            $from = $order->status;

            $order->update([
                'status' => $to,
                'cancelled_at' => $to === OrderStatus::Cancelled ? now() : $order->cancelled_at,
                'tracking_number' => $to === OrderStatus::Shipped ? $trackingNumber : $order->tracking_number,
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

        if ($to === OrderStatus::Shipped && $notifiesCustomer) {
            // 送信に失敗しても確定済みのステータスが巻き戻らないよう、トランザクションの外で送る
            $this->notificationMailer->send($order->customer_email, new OrderShipped($order));
        }
    }
}
