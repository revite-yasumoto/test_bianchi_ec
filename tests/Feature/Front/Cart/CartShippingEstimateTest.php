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
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartShippingEstimateTest extends TestCase
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

    private function addToCart(int $price, int $quantity = 1): void
    {
        $product = Product::factory()->create(['price' => $price]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);
        CartItem::factory()->create([
            'user_id' => $this->user->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 配送先が未登録なら東京都を基準に概算送料を算出する(): void
    {
        $this->addToCart(3000);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('estimatedPrefectureName', '東京都')
                ->where('estimatedShippingLabel', '500円')
                ->where('estimatedTotal', 3500)
            );
    }

    #[Test]
    public function 既定の配送先がある場合はその都道府県を基準に概算送料を算出する(): void
    {
        $hokkaido = Prefecture::factory()->create(['name' => '北海道']);
        ShippingSetting::factory()->create([
            'prefecture_id' => $hokkaido->id,
            'fee' => 1000,
        ]);
        UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $hokkaido->id,
            'is_default' => true,
        ]);
        $this->addToCart(3000);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('estimatedPrefectureName', '北海道')
                ->where('estimatedShippingLabel', '1,000円')
                ->where('estimatedTotal', 4000)
            );
    }

    #[Test]
    public function 既定でない配送先しかない場合は東京都を基準にする(): void
    {
        $osaka = Prefecture::factory()->create(['name' => '大阪府']);
        ShippingSetting::factory()->create([
            'prefecture_id' => $osaka->id,
            'fee' => 800,
        ]);
        UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $osaka->id,
            'is_default' => false,
        ]);
        $this->addToCart(3000);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('estimatedPrefectureName', '東京都')
                ->where('estimatedShippingLabel', '500円')
            );
    }

    #[Test]
    public function 商品合計が送料無料のしきい値と同額なら概算送料が無料になる(): void
    {
        $this->addToCart(10000);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('estimatedShippingLabel', '無料')
                ->where('estimatedTotal', 10000)
                ->where('remainingForFreeShipping', 0)
            );
    }

    #[Test]
    public function しきい値に満たない場合は残額が案内される(): void
    {
        $this->addToCart(3000, 3);

        $this->actingAs($this->user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('subtotal', 9000)
                ->where('freeShippingThreshold', 10000)
                ->where('remainingForFreeShipping', 1000)
            );
    }
}
