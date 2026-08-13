<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\PaymentMethod;
use App\Models\CartItem;
use App\Models\Prefecture;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Front\Checkout\CheckoutService;

/**
 * 購入手続き〜注文確定のテストで共通に必要な、送料設定・在庫付き商品・カート明細の生成をまとめる。
 */
trait CreatesCheckoutScenario
{
    protected function createPrefectureWithShipping(
        string $name,
        int $fee = 500,
        int $deliveryDays = 3,
    ): Prefecture {
        $prefecture = Prefecture::factory()->create(['name' => $name]);

        ShippingSetting::factory()->create([
            'prefecture_id' => $prefecture->id,
            'fee' => $fee,
            'delivery_days' => $deliveryDays,
        ]);

        return $prefecture;
    }

    /**
     * @param  array<string, mixed>  $productAttributes
     * @param  array<string, mixed>  $variantAttributes
     */
    protected function createVariantWithStock(
        int $price = 3000,
        int $stock = 10,
        array $productAttributes = [],
        array $variantAttributes = [],
    ): ProductVariant {
        $variant = ProductVariant::factory()
            ->for(Product::factory()->create([...$productAttributes, 'price' => $price]))
            ->create($variantAttributes);

        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $stock,
        ]);

        return $variant;
    }

    protected function addToCart(User $user, ProductVariant $variant, int $quantity = 1): CartItem
    {
        return CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * 購入手続きで選択済みの状態（配送先・支払い方法）をセッションに積む。
     *
     * @return array<string, mixed>
     */
    protected function checkoutSession(UserAddress $address, PaymentMethod $paymentMethod): array
    {
        return [
            CheckoutService::SESSION_ADDRESS_ID => $address->id,
            CheckoutService::SESSION_PAYMENT_METHOD => $paymentMethod->value,
        ];
    }
}
