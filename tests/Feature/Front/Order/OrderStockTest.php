<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Order;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    private Prefecture $tokyo;

    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        EcSetting::factory()->create();
        $this->tokyo = $this->createPrefectureWithShipping('東京都', 500);
        $this->address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
        ]);
    }

    private function placeOrder(): TestResponse
    {
        return $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::BankTransfer))
            ->post(route('orders.store'));
    }

    #[Test]
    public function 在庫が明細の数量分だけ減算される(): void
    {
        $variant = $this->createVariantWithStock(3000, 10);
        $this->addToCart($this->user, $variant, 3);

        $this->placeOrder();

        $this->assertDatabaseHas('stocks', [
            'product_variant_id' => $variant->id,
            'quantity' => 7,
        ]);
    }

    #[Test]
    public function 複数明細ではそれぞれの在庫が減算される(): void
    {
        $first = $this->createVariantWithStock(3000, 5);
        $second = $this->createVariantWithStock(1500, 2);
        $this->addToCart($this->user, $first, 2);
        $this->addToCart($this->user, $second, 1);

        $this->placeOrder();

        $this->assertDatabaseHas('stocks', ['product_variant_id' => $first->id, 'quantity' => 3]);
        $this->assertDatabaseHas('stocks', ['product_variant_id' => $second->id, 'quantity' => 1]);
    }

    #[Test]
    public function 在庫を全て購入すると在庫が0になる(): void
    {
        $variant = $this->createVariantWithStock(3000, 2);
        $this->addToCart($this->user, $variant, 2);

        $this->placeOrder();

        $this->assertDatabaseHas('stocks', ['product_variant_id' => $variant->id, 'quantity' => 0]);
    }

    #[Test]
    public function 在庫が不足していると注文が作成されず在庫も減らない(): void
    {
        $variant = $this->createVariantWithStock(3000, 5);
        $this->addToCart($this->user, $variant, 5);
        // カート投入後に在庫が減ったケースを再現する
        Stock::query()->where('product_variant_id', $variant->id)->update(['quantity' => 4]);

        $this->placeOrder()
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', '在庫が不足している商品があります。カートの内容をご確認ください。');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('stocks', ['product_variant_id' => $variant->id, 'quantity' => 4]);
    }

    #[Test]
    public function 一部の明細が在庫不足なら全ての書き込みが取り消される(): void
    {
        $available = $this->createVariantWithStock(3000, 10);
        $shortage = $this->createVariantWithStock(1500, 10);
        $this->addToCart($this->user, $available, 2);
        $this->addToCart($this->user, $shortage, 3);
        Stock::query()->where('product_variant_id', $shortage->id)->update(['quantity' => 1]);

        $this->placeOrder()->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_status_histories', 0);
        $this->assertDatabaseHas('stocks', ['product_variant_id' => $available->id, 'quantity' => 10]);
        $this->assertDatabaseHas('stocks', ['product_variant_id' => $shortage->id, 'quantity' => 1]);
        // カートも残したままにして、会員が数量を直せる状態を保つ
        $this->assertDatabaseCount('cart_items', 2);
    }

    #[Test]
    public function 非公開になった商品が含まれていると注文が作成されない(): void
    {
        $variant = $this->createVariantWithStock(3000, 10);
        $this->addToCart($this->user, $variant, 1);
        $variant->product->update(['is_published' => false]);

        $this->placeOrder()->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('stocks', ['product_variant_id' => $variant->id, 'quantity' => 10]);
    }

    #[Test]
    public function 取扱対象外になった規格が含まれていると注文が作成されない(): void
    {
        $variant = $this->createVariantWithStock(3000, 10);
        $this->addToCart($this->user, $variant, 1);
        $variant->update(['is_available' => false]);

        $this->placeOrder()->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('orders', 0);
    }
}
