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

class UpdateCartItemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function makeCartItem(User $user, int $quantity = 1, int $stock = 10): CartItem
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $stock,
        ]);

        return CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 数量を変更できる(): void
    {
        $cartItem = $this->makeCartItem($this->user);

        $this->actingAs($this->user)
            ->put(route('cart.items.update', $cartItem), ['quantity' => 3])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function 在庫数を超える数量には変更できない(): void
    {
        $cartItem = $this->makeCartItem($this->user, quantity: 2, stock: 3);

        $this->actingAs($this->user)
            ->put(route('cart.items.update', $cartItem), ['quantity' => 4])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function 数量を0以下には変更できない(): void
    {
        $cartItem = $this->makeCartItem($this->user, quantity: 2);

        $this->actingAs($this->user)
            ->put(route('cart.items.update', $cartItem), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function 数量の上限を超える値には変更できない(): void
    {
        $cartItem = $this->makeCartItem($this->user, quantity: 2, stock: 200);

        $this->actingAs($this->user)
            ->put(route('cart.items.update', $cartItem), ['quantity' => 100])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function 他人のカート行は変更できない(): void
    {
        $cartItem = $this->makeCartItem(User::factory()->create(), quantity: 2);

        $this->actingAs($this->user)
            ->put(route('cart.items.update', $cartItem), ['quantity' => 3])
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function 未ログインでは数量を変更できない(): void
    {
        $cartItem = $this->makeCartItem($this->user, quantity: 2);

        $this->put(route('cart.items.update', $cartItem), ['quantity' => 3])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }
}
