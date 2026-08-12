<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Product;

use App\Models\EcSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductStockDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EcSetting::factory()->create();
    }

    private function makeVariant(Product $product, int $quantity, bool $isAvailable = true): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_available' => $isAvailable,
        ]);

        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 全てのバリエーションの在庫が0なら在庫切れになる(): void
    {
        $product = Product::factory()->create(['has_sku' => true]);
        $this->makeVariant($product, 0);
        $this->makeVariant($product, 0);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page->where('product.is_sold_out', true));

        $this->get(route('products.index'))
            ->assertInertia(fn ($page) => $page->where('products.data.0.is_sold_out', true));
    }

    #[Test]
    public function 一件でも在庫があれば在庫ありになる(): void
    {
        $product = Product::factory()->create(['has_sku' => true]);
        $this->makeVariant($product, 0);
        $this->makeVariant($product, 1);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page->where('product.is_sold_out', false));

        $this->get(route('products.index'))
            ->assertInertia(fn ($page) => $page->where('products.data.0.is_sold_out', false));
    }

    #[Test]
    public function 取扱対象外のバリエーションは在庫判定から除外される(): void
    {
        $product = Product::factory()->create(['has_sku' => true]);
        $this->makeVariant($product, 0);
        $this->makeVariant($product, 5, isAvailable: false);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page->where('product.is_sold_out', true));

        $this->get(route('products.index'))
            ->assertInertia(fn ($page) => $page->where('products.data.0.is_sold_out', true));
    }

    #[Test]
    public function 在庫レコードが無いバリエーションは在庫切れとして扱う(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page
                ->where('product.is_sold_out', true)
                ->where('product.variants.0.in_stock', false)
            );
    }
}
