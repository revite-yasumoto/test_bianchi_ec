<?php

declare(strict_types=1);

namespace App\Services\Front\Order;

use App\Actions\Front\Order\BuildOrderItemSnapshots;
use App\Actions\Front\Order\BuildOrderSnapshot;
use App\Actions\Front\Order\GenerateOrderNumber;
use App\Enums\PaymentMethod;
use App\Exceptions\OrderNotPlaceableException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Shipping\ShippingCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * 注文確定処理。スナップショットの書き込み・在庫の減算・カートのクリアを1トランザクションで行う。
 * 金額は選択内容から必ず再計算し、画面やセッションが持っていた金額を信用しない。
 */
class PlaceOrderService
{
    public function __construct(
        private readonly ShippingCalculator $shippingCalculator,
        private readonly GenerateOrderNumber $generateOrderNumber,
        private readonly BuildOrderSnapshot $buildOrderSnapshot,
        private readonly BuildOrderItemSnapshots $buildOrderItemSnapshots,
    ) {}

    /**
     * @throws OrderNotPlaceableException 在庫不足・カートが空・配送先が無効のとき
     */
    public function place(User $user, int $addressId, PaymentMethod $paymentMethod): Order
    {
        return DB::transaction(function () use ($user, $addressId, $paymentMethod): Order {
            $cartItems = $this->cartItems($user);
            $stocks = $this->lockStocks($cartItems);
            $this->assertPurchasable($cartItems, $stocks);

            $address = $this->address($user, $addressId);

            $subtotal = (int) $cartItems->sum(
                fn (CartItem $item): int => $item->variant->product->price * $item->quantity,
            );
            $calculation = $this->shippingCalculator->calculate(
                $address->prefecture_id,
                $subtotal,
                $paymentMethod,
            );

            $orderedAt = CarbonImmutable::now();
            $order = Order::create(($this->buildOrderSnapshot)(
                $user,
                $address,
                $paymentMethod,
                $subtotal,
                $calculation,
                ($this->generateOrderNumber)($orderedAt),
                $orderedAt,
            ));

            $order->items()->createMany(($this->buildOrderItemSnapshots)($cartItems));

            foreach ($cartItems as $item) {
                $stocks->get($item->product_variant_id)?->decrement('quantity', $item->quantity);
            }

            $order->statusHistories()->create([
                'admin_id' => null,
                'from_status' => null,
                'to_status' => $order->status->value,
                'changed_at' => $orderedAt,
            ]);

            $user->cartItems()->delete();

            return $order;
        });
    }

    /**
     * @return Collection<int, CartItem>
     */
    private function cartItems(User $user): Collection
    {
        $cartItems = $user->cartItems()
            ->with(['variant.product.category', 'variant.product.mainImage'])
            ->orderBy('id')
            ->get();

        if ($cartItems->isEmpty()) {
            throw new OrderNotPlaceableException('カートに商品がありません。');
        }

        return $cartItems;
    }

    /**
     * 同時注文で在庫がマイナスになるのを防ぐため、検証より前に在庫行をロックする。
     *
     * @param  Collection<int, CartItem>  $cartItems
     * @return SupportCollection<int, Stock>
     */
    private function lockStocks(Collection $cartItems): SupportCollection
    {
        return Stock::query()
            ->whereIn('product_variant_id', $cartItems->pluck('product_variant_id')->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('product_variant_id');
    }

    /**
     * @param  Collection<int, CartItem>  $cartItems
     * @param  SupportCollection<int, Stock>  $stocks
     */
    private function assertPurchasable(Collection $cartItems, SupportCollection $stocks): void
    {
        foreach ($cartItems as $item) {
            $variant = $item->variant;
            $quantity = $stocks->get($item->product_variant_id)?->quantity ?? 0;

            if (! $variant->product->is_published || ! $variant->is_available || $quantity < $item->quantity) {
                throw new OrderNotPlaceableException('在庫が不足している商品があります。カートの内容をご確認ください。');
            }
        }
    }

    private function address(User $user, int $addressId): UserAddress
    {
        /** @var UserAddress|null $address */
        $address = $user->addresses()->with('prefecture')->find($addressId);

        if (! $address) {
            throw new OrderNotPlaceableException('お届け先を選び直してください。', 'checkout.index');
        }

        return $address;
    }
}
