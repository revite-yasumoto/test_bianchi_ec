<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Enums\PaymentMethod;
use App\Models\ShippingSetting;
use App\Services\Setting\EcSettingProvider;
use Carbon\CarbonImmutable;

/**
 * 配送先の都道府県と商品合計から、送料・代引き手数料・配達予定日・合計金額を算出する。
 * カート・購入手続き・注文確定はこのクラスの結果のみを金額の根拠とする。
 */
class ShippingCalculator
{
    public function __construct(private readonly EcSettingProvider $ecSettingProvider) {}

    /**
     * @param  int  $itemsTotal  商品合計（税込）
     */
    public function calculate(int $prefectureId, int $itemsTotal, PaymentMethod $paymentMethod): ShippingCalculation
    {
        $shippingSetting = ShippingSetting::query()
            ->where('prefecture_id', $prefectureId)
            ->firstOrFail();

        $ecSetting = $this->ecSettingProvider->get();

        // 送料無料の判定は商品合計のみで行う。送料・代引き手数料は判定に含めない
        $fee = $itemsTotal >= $ecSetting->free_shipping_threshold ? 0 : $shippingSetting->fee;
        $codFee = $paymentMethod === PaymentMethod::Cod ? $ecSetting->cod_fee : 0;

        return new ShippingCalculation(
            feeBase: $shippingSetting->fee,
            fee: $fee,
            codFee: $codFee,
            deliveryDays: $shippingSetting->delivery_days,
            // 営業日・休業日は考慮せず暦日で加算する
            estimatedDeliveryDate: CarbonImmutable::today()->addDays($shippingSetting->delivery_days),
            total: $itemsTotal + $fee + $codFee,
            freeShippingThreshold: $ecSetting->free_shipping_threshold,
        );
    }
}
