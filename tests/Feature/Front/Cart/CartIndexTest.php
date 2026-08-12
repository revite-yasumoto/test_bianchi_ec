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
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartIndexTest extends TestCase
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

    private function makeCartItem(User $user, int $price = 3000, int $quantity = 1): CartItem
    {
        $product = Product::factory()->create(['price' => $price]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        return CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('cart.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 自分のカート行だけが表示される(): void
    {
        $mine = $this->makeCartItem($this->user);
        $this->makeCartItem(User::factory()->create());

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->component('front/Cart/Index')
                ->has('items', 1)
                ->where('items.0.id', $mine->id)
            );
    }

    #[Test]
    public function カートが空のとき明細も合計も0になる(): void
    {
        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->has('items', 0)
                ->where('subtotal', 0)
            );
    }

    #[Test]
    public function 明細の小計と商品合計が単価と数量から算出される(): void
    {
        $this->makeCartItem($this->user, price: 3000, quantity: 2);
        $this->makeCartItem($this->user, price: 1500, quantity: 1);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('items.0.unit_price', 3000)
                ->where('items.0.subtotal', 6000)
                ->where('items.1.subtotal', 1500)
                ->where('subtotal', 7500)
            );
    }

    #[Test]
    public function 明細が増えてもクエリ数が変わらない(): void
    {
        $this->makeCartItem($this->user);

        DB::enableQueryLog();
        $this->actingAs($this->user)->get(route('cart.index'));
        $singleItemQueries = count(DB::getQueryLog());

        $this->makeCartItem($this->user);
        $this->makeCartItem($this->user);

        DB::flushQueryLog();
        $this->actingAs($this->user)->get(route('cart.index'));
        $multipleItemQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($singleItemQueries, $multipleItemQueries);
    }
}
