<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddToCartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function makeVariant(
        int $quantity = 5,
        bool $isAvailable = true,
        bool $isPublished = true,
    ): ProductVariant {
        $product = Product::factory()->create(['is_published' => $isPublished]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_available' => $isAvailable,
        ]);

        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);

        return $variant;
    }

    #[Test]
    public function カートに追加できる(): void
    {
        $variant = $this->makeVariant();

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $this->user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $variant = $this->makeVariant();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function 在庫が0のバリエーションは追加できない(): void
    {
        $variant = $this->makeVariant(quantity: 0);

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function 取扱対象外のバリエーションは追加できない(): void
    {
        $variant = $this->makeVariant(isAvailable: false);

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function 非公開商品のバリエーションは追加できない(): void
    {
        $variant = $this->makeVariant(isPublished: false);

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function 在庫数を超える数量は追加できない(): void
    {
        $variant = $this->makeVariant(quantity: 2);

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function 同じバリエーションを追加すると数量が加算される(): void
    {
        $variant = $this->makeVariant(quantity: 5);
        CartItem::factory()->create([
            'user_id' => $this->user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function 加算後に在庫数を超える場合は追加できない(): void
    {
        $variant = $this->makeVariant(quantity: 3);
        CartItem::factory()->create([
            'user_id' => $this->user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function 存在しないバリエーションは追加できない(): void
    {
        $this->actingAs($this->user)
            ->post(route('cart.items.store'), [
                'product_variant_id' => 999,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_variant_id');
    }
}
