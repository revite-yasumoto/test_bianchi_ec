<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrderHistoryController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user('web');

        $orders = $user->orders()
            ->select(['id', 'order_number', 'ordered_at', 'status', 'total'])
            ->with('items:id,order_id,product_name')
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'ordered_at' => $order->ordered_at->toISOString(),
                'status_label' => $order->status->label(),
                'status_tone' => $this->toneOf($order->status),
                'total' => $order->total,
                'items_summary' => $this->itemsSummaryOf($order),
                'is_cancelable' => $order->status->isCancelableByCustomer(),
            ]);

        return Inertia::render('front/MyPage/Orders', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items', 'statusHistories']);

        return Inertia::render('front/MyPage/OrderShow', [
            'order' => $this->detailOf($order),
        ]);
    }

    /**
     * 先頭の明細名に残りの点数を添える（例: チームジャージ 2026 ほか1点）。
     */
    private function itemsSummaryOf(Order $order): string
    {
        /** @var OrderItem $first */
        $first = $order->items->first();
        $rest = $order->items->count() - 1;

        return $rest > 0
            ? "{$first->product_name} ほか{$rest}点"
            : $first->product_name;
    }

    /**
     * 注文詳細はスナップショット列だけで組み立てる（products / users を参照しない）。
     *
     * @return array<string, mixed>
     */
    private function detailOf(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'ordered_at' => $order->ordered_at->toISOString(),
            'status_label' => $order->status->label(),
            'status_tone' => $this->toneOf($order->status),
            'payment_method_label' => $order->payment_method->label(),
            'bank_transfer_note' => $order->bank_transfer_note,
            'estimated_delivery_date' => $order->estimated_delivery_date->toDateString(),
            'items' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    // 画像未登録の明細にカテゴリ別のプレースホルダーを出すために渡す
                    'category_name' => $item->category_name,
                    'variant_label' => $this->variantLabelOf($item),
                    'product_image_url' => $item->product_image_path
                        ? Storage::disk('public')->url($item->product_image_path)
                        : null,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ])
                ->all(),
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'cod_fee' => $order->cod_fee,
            'total' => $order->total,
            'shipping' => [
                'recipient_name' => $order->shipping_recipient_name,
                'postal_code' => $order->shipping_postal_code,
                'prefecture_name' => $order->shipping_prefecture_name,
                'city' => $order->shipping_city,
                'address_line1' => $order->shipping_address_line1,
                'address_line2' => $order->shipping_address_line2,
                'tel' => $order->shipping_tel,
            ],
            // 会員向けには進行だけを示し、操作した管理者名は渡さない
            'histories' => $order->statusHistories
                ->sortBy('changed_at')
                ->values()
                ->map(fn (OrderStatusHistory $history): array => [
                    'id' => $history->id,
                    'to_status_label' => $history->to_status->label(),
                    'changed_at' => $history->changed_at->toISOString(),
                ])
                ->all(),
            'is_cancelable' => $order->status->isCancelableByCustomer(),
        ];
    }

    private function variantLabelOf(OrderItem $item): string
    {
        $parts = array_filter([$item->color_name, $item->size_name]);

        return $parts === [] ? '規格なし' : implode(' / ', $parts);
    }

    /**
     * @return array{fg: string, bg: string}
     */
    private function toneOf(OrderStatus $status): array
    {
        [$fg, $bg] = $status->color();

        return ['fg' => $fg, 'bg' => $bg];
    }
}
