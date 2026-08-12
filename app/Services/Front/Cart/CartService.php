<?php

declare(strict_types=1);

namespace App\Services\Front\Cart;

use App\Enums\PaymentMethod;
use App\Http\Requests\Front\Cart\StoreCartItemRequest;
use App\Models\CartItem;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\Shipping\ShippingCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * カートページのPropsを組み立てる。
 */
class CartService
{
    /** 既定の配送先が未登録の会員について、概算送料の基準にする都道府県 */
    private const FALLBACK_PREFECTURE_NAME = '東京都';

    public function __construct(private readonly ShippingCalculator $shippingCalculator) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $rows = $this->items($user)
            ->map(fn (CartItem $item): array => $this->row($item))
            ->all();

        $subtotal = array_sum(array_column($rows, 'subtotal'));
        $prefecture = $this->estimatedPrefecture($user);

        // 支払い方法は購入手続きで選ぶため、概算では代引き手数料の付かない銀行振込で算出する
        $calculation = $this->shippingCalculator->calculate(
            $prefecture->id,
            $subtotal,
            PaymentMethod::BankTransfer,
        );

        return [
            'items' => $rows,
            'subtotal' => $subtotal,
            'estimatedShippingLabel' => $calculation->fee === 0
                ? '無料'
                : number_format($calculation->fee).'円',
            'estimatedTotal' => $calculation->total,
            'freeShippingThreshold' => $calculation->freeShippingThreshold,
            'remainingForFreeShipping' => max(0, $calculation->freeShippingThreshold - $subtotal),
            'estimatedPrefectureName' => $prefecture->name,
        ];
    }

    /**
     * @return Collection<int, CartItem>
     */
    private function items(User $user): Collection
    {
        return $user->cartItems()
            ->with([
                'variant.product.category',
                'variant.product.mainImage',
                'variant.stock',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $variant->product;
        $stock = $variant->stock?->quantity ?? 0;

        return [
            'id' => $item->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_name' => $product->category->name,
            'variant_label' => $variant->displayName(),
            'main_image_url' => $product->mainImage
                ? Storage::disk('public')->url($product->mainImage->path)
                : null,
            // 単価はカート投入時ではなく現在の商品価格。価格が固定されるのは注文確定時
            'unit_price' => $product->price,
            'quantity' => $item->quantity,
            'subtotal' => $product->price * $item->quantity,
            'in_stock' => $stock > 0,
            'is_purchasable' => $product->is_published
                && $variant->is_available
                && $stock >= $item->quantity,
            'max_quantity' => min($stock, StoreCartItemRequest::MAX_QUANTITY),
        ];
    }

    private function estimatedPrefecture(User $user): Prefecture
    {
        $default = $user->addresses()
            ->with('prefecture')
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        return $default?->prefecture ?? Prefecture::query()
            ->where('name', self::FALLBACK_PREFECTURE_NAME)
            ->firstOrFail();
    }
}
