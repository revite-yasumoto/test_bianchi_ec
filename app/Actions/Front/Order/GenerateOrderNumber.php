<?php

declare(strict_types=1);

namespace App\Actions\Front\Order;

use App\Exceptions\OrderNotPlaceableException;
use App\Models\Order;
use Carbon\CarbonImmutable;

class GenerateOrderNumber
{
    /** 接頭辞はブランド表記の統一に従う */
    private const PREFIX = 'BNC';

    private const MAX_ATTEMPTS = 3;

    /**
     * `BNC-YYMM-NNNN` 形式の注文番号を採番する。`NNNN` は当月内の連番。
     *
     * キャンセル済みの注文も件数に含めるため連番に欠番が出るが、番号の一意性と月内での通し番号という
     * 性質は保たれる。同時注文で番号が衝突した場合は、空いている番号まで最大3回ずらす。
     */
    public function __invoke(CarbonImmutable $orderedAt): string
    {
        $monthlyCount = Order::query()
            ->where('ordered_at', '>=', $orderedAt->startOfMonth())
            ->where('ordered_at', '<', $orderedAt->startOfMonth()->addMonth())
            ->count();

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = sprintf(
                '%s-%s-%04d',
                self::PREFIX,
                $orderedAt->format('ym'),
                $monthlyCount + 1 + $attempt,
            );

            if (! Order::query()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new OrderNotPlaceableException('ただいま混み合っています。時間をおいて再度お試しください。');
    }
}
