<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;

class RestoreStockFromOrder
{
    /**
     * 注文明細の数量分だけ在庫を戻す。管理者のキャンセル操作と会員のキャンセル依頼の双方から使う。
     *
     * 商品が削除済みで `product_variant_id` が null の明細は戻す先が無いためスキップする。
     */
    public function __invoke(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_variant_id')->get() as $item) {
            /** @var OrderItem $item */
            Stock::query()
                ->where('product_variant_id', $item->product_variant_id)
                ->increment('quantity', $item->quantity);
        }
    }
}
