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

class CartPriceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 商品価格を変更するとカートの単価と小計も変更後の価格になる(): void
    {
        $user = User::factory()->create();
        EcSetting::factory()->create(['free_shipping_threshold' => 10000]);
        ShippingSetting::factory()->create([
            'prefecture_id' => Prefecture::factory()->create(['name' => '東京都'])->id,
            'fee' => 500,
        ]);

        $product = Product::factory()->create(['price' => 3000]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $product->update(['price' => 4000]);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('items.0.unit_price', 4000)
                ->where('items.0.subtotal', 8000)
                ->where('subtotal', 8000)
                ->where('estimatedTotal', 8500)
            );
    }
}
