<?php

declare(strict_types=1);

namespace App\Actions\Front\Product;

use App\Models\Product;
use App\Models\User;

/**
 * 会員の閲覧履歴を記録する。同一商品の再訪では行を増やさず最終閲覧日時のみ更新し、
 * 直近 KEEP_COUNT 件を超えた古い履歴は削除する。
 */
class RecordBrowsingHistory
{
    /** マイページ・TOPに表示する件数に合わせる */
    private const KEEP_COUNT = 6;

    public function __invoke(User $user, Product $product): void
    {
        $user->browsingHistories()->updateOrCreate(
            ['product_id' => $product->id],
            ['viewed_at' => now()],
        );

        $keepIds = $user->browsingHistories()
            ->orderByDesc('viewed_at')
            ->orderByDesc('id')
            ->limit(self::KEEP_COUNT)
            ->pluck('id');

        $user->browsingHistories()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
