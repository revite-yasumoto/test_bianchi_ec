<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Product;

use App\Enums\SpecOptionType;
use App\Models\EcSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\SpecOption;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // EC基本設定は単一行（id=1）のため、最初の1件がその設定になる
        EcSetting::factory()->create();
    }

    private function makeVariant(Product $product, ?string $size, ?string $color, int $quantity): ProductVariant
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => $size,
            'color_name' => $color,
        ]);

        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);

        return $variant;
    }

    #[Test]
    public function 未ログインでも公開商品の詳細が表示される(): void
    {
        $product = Product::factory()->create([
            'name' => 'ロードスター',
            'product_code' => 'RC7-105',
            'price' => 398000,
        ]);
        $this->makeVariant($product, null, null, 3);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Product/Show')
                ->where('product.name', 'ロードスター')
                ->where('product.product_code', 'RC7-105')
                ->where('product.price', 398000)
                ->where('product.has_sku', false)
                ->where('product.is_sold_out', false)
                ->where('isFavorited', false)
            );
    }

    #[Test]
    public function 非公開商品の詳細は見つからない(): void
    {
        $product = Product::factory()->create(['is_published' => false]);
        $this->makeVariant($product, null, null, 3);

        $this->get(route('products.show', $product))->assertNotFound();
    }

    #[Test]
    public function 画像とスペックが並び順で返る(): void
    {
        $product = Product::factory()->create();
        $this->makeVariant($product, null, null, 1);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/second.jpg',
            'sort_order' => 1,
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/first.jpg',
            'sort_order' => 0,
        ]);
        ProductSpec::factory()->create([
            'product_id' => $product->id,
            'label' => 'フレーム',
            'value' => 'カーボン',
            'sort_order' => 1,
        ]);
        ProductSpec::factory()->create([
            'product_id' => $product->id,
            'label' => '重量',
            'value' => '8.2kg',
            'sort_order' => 0,
        ]);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page
                ->has('product.images', 2)
                ->where('product.images.0.sort_order', 0)
                ->where('product.images.1.sort_order', 1)
                ->where('product.specs.0.label', '重量')
                ->where('product.specs.1.label', 'フレーム')
            );
    }

    #[Test]
    public function 規格あり商品の選択肢が規格管理の並び順で返る(): void
    {
        SpecOption::factory()->create(['type' => SpecOptionType::Size, 'name' => 'S', 'sort_order' => 1]);
        SpecOption::factory()->create(['type' => SpecOptionType::Size, 'name' => 'M', 'sort_order' => 2]);
        SpecOption::factory()->create(['type' => SpecOptionType::Color, 'name' => 'ブラック', 'sort_order' => 1]);
        SpecOption::factory()->create(['type' => SpecOptionType::Color, 'name' => 'レッド', 'sort_order' => 2]);

        $product = Product::factory()->create(['has_sku' => true]);
        $this->makeVariant($product, 'M', 'レッド', 2);
        $this->makeVariant($product, 'S', 'ブラック', 2);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page
                ->where('product.has_sku', true)
                ->where('product.sizes', ['S', 'M'])
                ->where('product.colors', ['ブラック', 'レッド'])
                ->has('product.variants', 2)
            );
    }

    #[Test]
    public function 規格管理に無い選択肢は末尾に回る(): void
    {
        SpecOption::factory()->create(['type' => SpecOptionType::Size, 'name' => 'M', 'sort_order' => 1]);

        $product = Product::factory()->create(['has_sku' => true]);
        $this->makeVariant($product, 'XL', 'ブラック', 1);
        $this->makeVariant($product, 'M', 'ブラック', 1);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page->where('product.sizes', ['M', 'XL']));
    }

    #[Test]
    public function バリエーションに在庫数は含まれない(): void
    {
        $product = Product::factory()->create();
        $this->makeVariant($product, null, null, 7);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page
                ->where('product.variants.0.in_stock', true)
                ->missing('product.variants.0.quantity')
                ->missing('product.variants.0.stock')
            );
    }

    #[Test]
    public function 送料表と基本設定が渡される(): void
    {
        $product = Product::factory()->create();
        $this->makeVariant($product, null, null, 1);

        $this->get(route('products.show', $product))
            ->assertInertia(fn ($page) => $page
                ->where('ecSetting.free_shipping_threshold', 10000)
                ->where('ecSetting.cod_fee', 330)
                ->has('shippingTable')
            );
    }
}
