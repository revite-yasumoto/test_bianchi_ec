<?php

declare(strict_types=1);

namespace App\Actions\Front\Contact;

use App\Models\Product;

class ResolveContactProduct
{
    /**
     * 対象商品を商品マスタから引き直す。
     *
     * クエリ・リクエストの値は未認証で到達でき配列や任意長の値を渡されうるため、
     * 数値として解釈できるものだけを商品IDとして扱う。
     */
    public function __invoke(mixed $productId): ?Product
    {
        if (! is_numeric($productId)) {
            return null;
        }

        return Product::query()
            ->select(['id', 'name', 'product_code'])
            ->where('is_published', true)
            ->find((int) $productId);
    }
}
