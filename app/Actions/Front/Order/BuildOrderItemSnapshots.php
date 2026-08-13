<?php

declare(strict_types=1);

namespace App\Actions\Front\Order;

use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * `order_items` に保存する商品スナップショットを組み立てる。
 * 商品名・カテゴリ名・規格名・画像・単価を値として複製するため、後から商品が変更・削除されても明細は変わらない。
 */
class BuildOrderItemSnapshots
{
    /**
     * @param  Collection<int, CartItem>  $cartItems  `variant.product.category` と `variant.product.mainImage` をロード済みであること
     * @return list<array<string, mixed>>
     */
    public function __invoke(Collection $cartItems): array
    {
        return $cartItems->map(function (CartItem $item): array {
            $variant = $item->variant;
            $product = $variant->product;

            return [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_code' => $product->product_code,
                'product_name' => $product->name,
                'category_name' => $product->category->name,
                'sku_code' => $variant->sku_code,
                'size_name' => $variant->size_name,
                'color_name' => $variant->color_name,
                'product_image_path' => $product->mainImage?->path,
                'unit_price' => $product->price,
                'quantity' => $item->quantity,
                'subtotal' => $product->price * $item->quantity,
            ];
        })->values()->all();
    }
}
