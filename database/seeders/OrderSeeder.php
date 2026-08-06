<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * 全6ステータスを網羅する注文7件を、注文時点のスナップショットを埋めて投入する。
     */
    public function run(): void
    {
        $ecSetting = EcSetting::query()->first();
        $tokyoShipping = ShippingSetting::query()->whereHas('prefecture', fn ($q) => $q->where('name', '東京都'))->first();
        $hokkaidoShipping = ShippingSetting::query()->whereHas('prefecture', fn ($q) => $q->where('name', '北海道'))->first();

        if ($ecSetting === null || $tokyoShipping === null || $hokkaidoShipping === null) {
            return;
        }

        $orders = [
            [
                'no' => 'BNC-2608-1042', 'user' => '山田 太郎', 'date' => '2026-08-06',
                'pay' => PaymentMethod::BankTransfer, 'status' => OrderStatus::AwaitingPayment,
                'shipping' => $tokyoShipping, 'items' => [['RC7-105', null, null, 398000, 1]],
            ],
            [
                'no' => 'BNC-2608-1041', 'user' => '佐藤 花子', 'date' => '2026-08-06',
                'pay' => PaymentMethod::Cod, 'status' => OrderStatus::Received,
                'shipping' => $tokyoShipping, 'items' => [
                    ['AP-JRS26', 'M', 'アクア', 14800, 1],
                    ['PT-BTL6', '600ml', 'アクア', 3200, 1],
                ],
            ],
            [
                'no' => 'BNC-2608-1038', 'user' => '鈴木 一郎', 'date' => '2026-08-05',
                'pay' => PaymentMethod::BankTransfer, 'status' => OrderStatus::PaymentConfirmed,
                'shipping' => $tokyoShipping, 'items' => [['PT-CGE', null, null, 5800, 1]],
            ],
            [
                'no' => 'BNC-2608-1035', 'user' => '田中 美咲', 'date' => '2026-08-04',
                'pay' => PaymentMethod::BankTransfer, 'status' => OrderStatus::Preparing,
                'shipping' => $tokyoShipping, 'items' => [['EV-URB', null, null, 328000, 1]],
            ],
            [
                'no' => 'BNC-2608-1030', 'user' => '高橋 健', 'date' => '2026-08-02',
                'pay' => PaymentMethod::Cod, 'status' => OrderStatus::Shipped,
                'shipping' => $tokyoShipping, 'items' => [['AP-JRS26', 'M', 'アクア', 14800, 1]],
            ],
            [
                'no' => 'BNC-2607-0994', 'user' => '伊藤 彩', 'date' => '2026-07-29',
                'pay' => PaymentMethod::BankTransfer, 'status' => OrderStatus::Shipped,
                'shipping' => $hokkaidoShipping, 'items' => [['MT3-STD', null, null, 198000, 1]],
            ],
            [
                'no' => 'BNC-2607-0981', 'user' => '高橋 健', 'date' => '2026-07-27',
                'pay' => PaymentMethod::Cod, 'status' => OrderStatus::Cancelled,
                'shipping' => $tokyoShipping, 'items' => [['PT-BTL6', '600ml', 'アクア', 3200, 1]],
            ],
        ];

        foreach ($orders as $data) {
            $this->createOrder($data, $ecSetting);
        }
    }

    /**
     * @param  array{no: string, user: string, date: string, pay: PaymentMethod, status: OrderStatus, shipping: ShippingSetting, items: array<int, array{0: string, 1: ?string, 2: ?string, 3: int, 4: int}>}  $data
     */
    private function createOrder(array $data, EcSetting $ecSetting): void
    {
        if (Order::query()->where('order_number', $data['no'])->exists()) {
            return;
        }

        $user = User::query()->where('name', $data['user'])->first();

        if ($user === null) {
            return;
        }

        $subtotal = array_sum(array_map(fn ($item) => $item[3] * $item[4], $data['items']));
        $freeShipping = $subtotal >= $ecSetting->free_shipping_threshold;
        $shippingFee = $freeShipping ? 0 : $data['shipping']->fee;
        $codFee = $data['pay'] === PaymentMethod::Cod ? $ecSetting->cod_fee : 0;
        $orderedAt = Carbon::parse($data['date'].' 10:00:00');

        $order = Order::query()->create([
            'order_number' => $data['no'],
            'user_id' => $user->id,
            'status' => $data['status'],
            'payment_method' => $data['pay'],
            'ordered_at' => $orderedAt,
            'cancelled_at' => $data['status'] === OrderStatus::Cancelled ? $orderedAt->copy()->addHour() : null,
            'member_code_snapshot' => $user->member_code,
            'customer_name' => $user->name,
            'customer_name_kana' => $user->name_kana,
            'customer_email' => $user->email,
            'customer_tel' => $user->tel,
            'shipping_recipient_name' => $user->name,
            'shipping_postal_code' => '150-0041',
            'shipping_prefecture_name' => $data['shipping']->prefecture->name,
            'shipping_city' => '渋谷区神南',
            'shipping_address_line1' => '1-2-3 サイクルレジデンス404',
            'shipping_address_line2' => null,
            'shipping_tel' => '090-1234-5678',
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'cod_fee' => $codFee,
            'total' => $subtotal + $shippingFee + $codFee,
            'free_shipping_threshold' => $ecSetting->free_shipping_threshold,
            'shipping_fee_base' => $data['shipping']->fee,
            'delivery_days' => $data['shipping']->delivery_days,
            'estimated_delivery_date' => $orderedAt->copy()->addDays($data['shipping']->delivery_days)->toDateString(),
            'bank_transfer_note' => $data['pay'] === PaymentMethod::BankTransfer ? $ecSetting->bank_transfer_note : null,
        ]);

        foreach ($data['items'] as [$code, $size, $color, $unitPrice, $quantity]) {
            $product = Product::query()->where('product_code', $code)->first();
            $variant = ProductVariant::query()
                ->where('product_id', $product?->id)
                ->where('size_name', $size)
                ->where('color_name', $color)
                ->first();

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product?->id,
                'product_variant_id' => $variant?->id,
                'product_code' => $code,
                'product_name' => $product?->name ?? $code,
                'category_name' => $product?->category?->name ?? '',
                'sku_code' => $variant?->sku_code,
                'size_name' => $size,
                'color_name' => $color,
                'product_image_path' => null,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
            ]);
        }

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'admin_id' => null,
            'from_status' => null,
            'to_status' => $data['status'],
            'changed_at' => $orderedAt,
        ]);
    }
}
