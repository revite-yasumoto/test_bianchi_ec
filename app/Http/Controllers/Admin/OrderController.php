<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Order\BuildOrderFilter;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request, BuildOrderFilter $buildFilter): Response
    {
        $filters = $this->filtersOf($request);

        $orders = $buildFilter($filters)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'ordered_at' => $order->ordered_at->format('Y.m.d'),
                'customer_name' => $order->customer_name,
                'total' => $order->total,
                'payment_method_label' => $order->payment_method->label(),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_tone' => $this->toneOf($order->status),
            ]);

        return Inertia::render('admin/Order/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'totalCount' => Order::query()->count(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items', 'statusHistories.admin']);

        return Inertia::render('admin/Order/Show', [
            'order' => $this->detailOf($order),
            'statusOptions' => $this->transitionOptions($order),
        ]);
    }

    /**
     * @return array{status: string, q: string|null}
     */
    private function filtersOf(Request $request): array
    {
        $status = (string) $request->input('status', 'all');
        $allowed = array_map(fn (OrderStatus $case): string => $case->value, OrderStatus::cases());

        return [
            'status' => in_array($status, $allowed, true) ? $status : 'all',
            'q' => $request->filled('q') ? $request->string('q')->toString() : null,
        ];
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
            'ordered_at' => $order->ordered_at->format('Y.m.d H:i'),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_tone' => $this->toneOf($order->status),
            'payment_method_label' => $order->payment_method->label(),
            'items' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'category_name' => $item->category_name,
                    'variant_label' => $this->variantLabelOf($item),
                    'sku_code' => $item->sku_code,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ])
                ->all(),
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'cod_fee' => $order->cod_fee,
            'total' => $order->total,
            'estimated_delivery_date' => $order->estimated_delivery_date->format('Y.m.d'),
            'bank_transfer_note' => $order->bank_transfer_note,
            'shipping' => [
                'recipient_name' => $order->shipping_recipient_name,
                'postal_code' => $order->shipping_postal_code,
                'prefecture_name' => $order->shipping_prefecture_name,
                'city' => $order->shipping_city,
                'address_line1' => $order->shipping_address_line1,
                'address_line2' => $order->shipping_address_line2,
                'tel' => $order->shipping_tel,
            ],
            'customer' => [
                'member_code' => $order->member_code_snapshot,
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'tel' => $order->customer_tel,
            ],
            'histories' => $order->statusHistories
                ->sortByDesc('changed_at')
                ->values()
                ->map(fn (OrderStatusHistory $history): array => [
                    'id' => $history->id,
                    'from_status_label' => $history->from_status?->label(),
                    'to_status_label' => $history->to_status->label(),
                    'admin_name' => $history->admin?->name,
                    'changed_at' => $history->changed_at->format('Y.m.d H:i'),
                ])
                ->all(),
        ];
    }

    private function variantLabelOf(OrderItem $item): string
    {
        $parts = array_filter([$item->color_name, $item->size_name]);

        return $parts === [] ? '規格なし' : implode(' / ', $parts);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (OrderStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            OrderStatus::cases(),
        );
    }

    /**
     * 現在のステータスから遷移できる先だけを選択肢にする。
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function transitionOptions(Order $order): array
    {
        return array_map(
            fn (OrderStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $order->status->allowedTransitions(),
        );
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
