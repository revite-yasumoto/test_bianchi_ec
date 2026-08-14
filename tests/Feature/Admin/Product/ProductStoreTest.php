<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_code' => 'RC7-105',
            'name' => 'ロードスター RC7',
            'category_id' => $this->category->id,
            'price' => 398000,
            'description' => 'カーボンフレームのロードバイク',
            'is_published' => true,
            'has_sku' => false,
            'specs' => [],
            'variants' => [
                [
                    'size_name' => null,
                    'color_name' => null,
                    'branch_code' => null,
                    'is_available' => true,
                    'quantity' => 12,
                ],
            ],
        ], $overrides);
    }

    #[Test]
    public function 商品登録画面が表示される(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.create'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Product/Form')
                ->where('product', null)
                ->has('categories', 1)
            );
    }

    #[Test]
    public function バリエーションなし商品を登録すると在庫が1件作られる(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $this->payload());

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('product_code', 'RC7-105')->sole();
        $this->assertFalse($product->has_sku);
        $this->assertCount(1, $product->variants);

        $variant = $product->variants->first();
        $this->assertNull($variant->size_name);
        $this->assertNull($variant->color_name);
        $this->assertSame('RC7-105', $variant->sku_code);
        $this->assertSame(12, $variant->stock->quantity);
    }

    #[Test]
    public function バリエーションあり商品を登録すると組み合わせの数だけ作られる(): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            $this->payload([
                'has_sku' => true,
                'variants' => [
                    ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 3],
                    ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => '12', 'is_available' => true, 'quantity' => 5],
                    ['size_name' => 'M', 'color_name' => 'アクア', 'branch_code' => '21', 'is_available' => true, 'quantity' => 0],
                ],
            ]),
        );

        $product = Product::query()->where('product_code', 'RC7-105')->sole();

        $this->assertTrue($product->has_sku);
        $this->assertCount(3, $product->variants);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size_name' => 'L',
            'color_name' => 'ネイビー',
            'branch_code' => '12',
            'sku_code' => 'RC7-105-12',
        ]);
        $this->assertSame(8, (int) $product->stocks()->sum('quantity'));
    }

    #[Test]
    public function 取扱対象外の組み合わせはコードが空で在庫が0になる(): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            $this->payload([
                'has_sku' => true,
                'variants' => [
                    ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 3],
                    ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => null, 'is_available' => false, 'quantity' => 99],
                ],
            ]),
        );

        $product = Product::query()->where('product_code', 'RC7-105')->sole();
        $unavailable = $product->variants()
            ->where('size_name', 'L')
            ->sole();

        $this->assertFalse($unavailable->is_available);
        $this->assertNull($unavailable->sku_code);
        $this->assertSame(0, $unavailable->stock->quantity);
    }

    #[Test]
    public function 商品スペックが並び順付きで保存される(): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            $this->payload([
                'specs' => [
                    ['label' => 'フレーム', 'value' => 'カーボンモノコック'],
                    ['label' => '重量', 'value' => '7.8kg'],
                ],
            ]),
        );

        $product = Product::query()->where('product_code', 'RC7-105')->sole();

        $this->assertDatabaseHas('product_specs', [
            'product_id' => $product->id,
            'label' => 'フレーム',
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('product_specs', [
            'product_id' => $product->id,
            'label' => '重量',
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function 未認証は商品を登録できない(): void
    {
        $this->post(route('admin.products.store'), $this->payload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('products', 0);
        $this->assertSame(0, ProductVariant::query()->count());
    }
}
