<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Cart;

use App\Models\CartItem;
use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartStockGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        EcSetting::factory()->create(['free_shipping_threshold' => 10000]);
        ShippingSetting::factory()->create([
            'prefecture_id' => Prefecture::factory()->create(['name' => '東京都'])->id,
            'fee' => 500,
        ]);
    }

    private function makeCartItem(
        int $quantity = 1,
        int $stock = 10,
        bool $isPublished = true,
        bool $isAvailable = true,
    ): CartItem {
        $product = Product::factory()->create(['is_published' => $isPublished]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_available' => $isAvailable,
        ]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $stock,
        ]);

        return CartItem::factory()->create([
            'user_id' => $this->user->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 在庫がある行は購入可能として表示される(): void
    {
        $this->makeCartItem();

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('items.0.in_stock', true)
                ->where('items.0.is_purchasable', true)
            );
    }

    #[Test]
    public function 在庫切れになった行は購入不可として表示される(): void
    {
        $this->makeCartItem(stock: 0);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->has('items', 1)
                ->where('items.0.in_stock', false)
                ->where('items.0.is_purchasable', false)
            );
    }

    #[Test]
    public function 在庫が数量に満たない行は購入不可として表示される(): void
    {
        $this->makeCartItem(quantity: 3, stock: 2);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('items.0.in_stock', true)
                ->where('items.0.is_purchasable', false)
                ->where('items.0.max_quantity', 2)
            );
    }

    #[Test]
    public function 非公開になった商品の行は購入不可として表示される(): void
    {
        $this->makeCartItem(isPublished: false);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->has('items', 1)
                ->where('items.0.in_stock', true)
                ->where('items.0.is_purchasable', false)
            );
    }

    #[Test]
    public function 取扱対象外になったバリエーションの行は購入不可として表示される(): void
    {
        $this->makeCartItem(isAvailable: false);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('items.0.is_purchasable', false)
            );
    }

    #[Test]
    public function 数量の上限は在庫数と数量上限のうち小さいほうになる(): void
    {
        $this->makeCartItem(stock: 200);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('items.0.max_quantity', 99)
            );
    }
}
