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

class DestroyCartItemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function makeCartItem(User $user): CartItem
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        return CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
    }

    #[Test]
    public function カートから商品を削除できる(): void
    {
        $cartItem = $this->makeCartItem($this->user);

        $this->actingAs($this->user)
            ->delete(route('cart.items.destroy', $cartItem))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    #[Test]
    public function 他人のカート行は削除できない(): void
    {
        $cartItem = $this->makeCartItem(User::factory()->create());

        $this->actingAs($this->user)
            ->delete(route('cart.items.destroy', $cartItem))
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id]);
    }

    #[Test]
    public function 未ログインでは削除できない(): void
    {
        $cartItem = $this->makeCartItem($this->user);

        $this->delete(route('cart.items.destroy', $cartItem))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id]);
    }
}
