<?php

declare(strict_types=1);

namespace App\Actions\Front\Order;

use App\Enums\PaymentMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Setting\EcSettingProvider;
use App\Services\Shipping\ShippingCalculation;
use Carbon\CarbonImmutable;

/**
 * `orders` に保存する注文時スナップショットを組み立てる。
 * 会員・配送先・EC基本設定・送料設定が後から変わっても注文の内容が変わらないよう、
 * 参照ではなく値として複製する。
 */
class BuildOrderSnapshot
{
    public function __construct(private readonly EcSettingProvider $ecSettingProvider) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        User $user,
        UserAddress $address,
        PaymentMethod $paymentMethod,
        int $subtotal,
        ShippingCalculation $calculation,
        string $orderNumber,
        CarbonImmutable $orderedAt,
    ): array {
        $ecSetting = $this->ecSettingProvider->get();

        return [
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'status' => $paymentMethod->initialOrderStatus(),
            'payment_method' => $paymentMethod,
            'ordered_at' => $orderedAt,

            'member_code_snapshot' => $user->member_code,
            'customer_name' => $user->name,
            'customer_name_kana' => $user->name_kana,
            'customer_email' => $user->email,
            'customer_tel' => $user->tel,

            'shipping_recipient_name' => $address->recipient_name,
            'shipping_postal_code' => $address->postal_code,
            // 都道府県は設定の付け替えに影響されないよう、IDではなく名称で保存する
            'shipping_prefecture_name' => $address->prefecture->name,
            'shipping_city' => $address->city,
            'shipping_address_line1' => $address->address_line1,
            'shipping_address_line2' => $address->address_line2,
            'shipping_tel' => $address->tel,

            'subtotal' => $subtotal,
            'shipping_fee' => $calculation->fee,
            'cod_fee' => $calculation->codFee,
            'total' => $calculation->total,

            'free_shipping_threshold' => $calculation->freeShippingThreshold,
            'shipping_fee_base' => $calculation->feeBase,
            'delivery_days' => $calculation->deliveryDays,
            'estimated_delivery_date' => $calculation->estimatedDeliveryDate,
            'bank_transfer_note' => $paymentMethod === PaymentMethod::BankTransfer
                ? $ecSetting->bank_transfer_note
                : null,
        ];
    }
}
