<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use Carbon\CarbonImmutable;

/**
 * 送料・配達予定日・合計金額の算出結果。
 * 各項目は注文確定時に `orders` のスナップショット列へそのまま保存される。
 */
final readonly class ShippingCalculation
{
    public function __construct(
        public int $feeBase,
        public int $fee,
        public int $codFee,
        public int $deliveryDays,
        public CarbonImmutable $estimatedDeliveryDate,
        public int $total,
        public int $freeShippingThreshold,
    ) {}
}
