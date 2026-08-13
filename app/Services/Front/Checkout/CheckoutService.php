<?php

declare(strict_types=1);

namespace App\Services\Front\Checkout;

use App\Enums\PaymentMethod;
use App\Models\Prefecture;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Front\Cart\CartService;
use App\Services\Setting\EcSettingProvider;
use App\Services\Shipping\ShippingCalculator;
use Carbon\CarbonImmutable;

/**
 * 購入手続き・注文確認のPropsを組み立てる。
 * 表示する金額はいずれも都度サーバー側で算出し、セッションに保存した金額を使わない。
 */
class CheckoutService
{
    /** 購入手続きで選んだ内容の持ち回りに使うセッションキー */
    public const SESSION_ADDRESS_ID = 'checkout.address_id';

    public const SESSION_PAYMENT_METHOD = 'checkout.payment_method';

    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly EcSettingProvider $ecSettingProvider,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function cartRows(User $user): array
    {
        return $this->cartService->rows($user);
    }

    /**
     * 購入手続きに進めない理由。進めるなら null。
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function blockingReason(array $rows): ?string
    {
        if ($rows === []) {
            return 'カートに商品がありません。';
        }

        foreach ($rows as $row) {
            if (! $row['is_purchasable']) {
                return '在庫が不足している商品があります。数量を変更するか、その商品を削除してください。';
            }
        }

        return null;
    }

    /**
     * セッションで選択済みの配送先。未選択・他人の住所・削除済みのときは null。
     */
    public function selectedAddress(User $user): ?UserAddress
    {
        $addressId = session(self::SESSION_ADDRESS_ID);

        if (! is_int($addressId)) {
            return null;
        }

        return $user->addresses()->with('prefecture')->find($addressId);
    }

    public function selectedPaymentMethod(): ?PaymentMethod
    {
        $value = session(self::SESSION_PAYMENT_METHOD);

        return is_string($value) ? PaymentMethod::tryFrom($value) : null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function buildIndex(User $user, array $rows): array
    {
        $addresses = $user->addresses()
            ->with('prefecture')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $ecSetting = $this->ecSettingProvider->get();

        return [
            'items' => $rows,
            'addresses' => $addresses->map(fn (UserAddress $address): array => $this->addressData($address))->all(),
            'prefectures' => Prefecture::query()->orderBy('id')->get(['id', 'name'])->all(),
            'shippingByPrefecture' => $this->shippingByPrefecture(),
            'selected' => [
                // 既定の配送先を先頭に並べているため、未選択のときは先頭が既定（無ければ最初の住所）になる
                'address_id' => $this->selectedAddress($user)?->id ?? $addresses->first()?->id,
                'payment_method' => ($this->selectedPaymentMethod() ?? PaymentMethod::BankTransfer)->value,
            ],
            'ecSetting' => [
                'free_shipping_threshold' => $ecSetting->free_shipping_threshold,
                'cod_fee' => $ecSetting->cod_fee,
            ],
            // 配送先を切り替えたときの配達予定日をフロント側で算出する基準日
            'deliveryBaseDate' => CarbonImmutable::today()->toDateString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function buildConfirm(array $rows, UserAddress $address, PaymentMethod $paymentMethod): array
    {
        $subtotal = array_sum(array_column($rows, 'subtotal'));
        $calculation = $this->shippingCalculator->calculate($address->prefecture_id, $subtotal, $paymentMethod);

        return [
            'items' => $rows,
            'address' => $this->addressData($address),
            'paymentMethod' => $paymentMethod->value,
            'amounts' => [
                'subtotal' => $subtotal,
                'shipping_fee' => $calculation->fee,
                'cod_fee' => $calculation->codFee,
                'total' => $calculation->total,
                'estimated_delivery_date' => $calculation->estimatedDeliveryDate->toDateString(),
            ],
            'bankTransferNote' => $paymentMethod === PaymentMethod::BankTransfer
                ? $this->ecSettingProvider->get()->bank_transfer_note
                : null,
        ];
    }

    /**
     * 都道府県ID → 送料・お届け日数。配送先を切り替えたときの再計算にフロント側で使う。
     *
     * @return array<int, array{fee: int, delivery_days: int}>
     */
    private function shippingByPrefecture(): array
    {
        return ShippingSetting::query()
            ->get(['prefecture_id', 'fee', 'delivery_days'])
            ->mapWithKeys(fn (ShippingSetting $setting): array => [
                $setting->prefecture_id => [
                    'fee' => $setting->fee,
                    'delivery_days' => $setting->delivery_days,
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function addressData(UserAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'postal_code' => $address->postal_code,
            'prefecture_id' => $address->prefecture_id,
            'prefecture_name' => $address->prefecture->name,
            'city' => $address->city,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'tel' => $address->tel,
            'is_default' => $address->is_default,
        ];
    }
}
