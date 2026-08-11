<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
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
     * カラー1色×サイズ2種の商品を作る。
     */
    private function makeSkuProduct(string $code = 'JS-2026'): Product
    {
        $product = Product::factory()->create([
            'product_code' => $code,
            'category_id' => $this->category->id,
            'has_sku' => true,
        ]);

        foreach ([['M', '11', 3], ['L', '12', 5]] as [$size, $branch, $quantity]) {
            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'size_name' => $size,
                'color_name' => 'ネイビー',
                'branch_code' => $branch,
                'sku_code' => $code.'-'.$branch,
                'is_available' => true,
            ]);

            Stock::factory()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ]);
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'product_code' => $product->product_code,
            'name' => $product->name,
            'category_id' => $this->category->id,
            'price' => $product->price,
            'description' => $product->description,
            'is_published' => $product->is_published,
            'has_sku' => true,
            'specs' => [],
            'variants' => [
                ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 3],
                ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => '12', 'is_available' => true, 'quantity' => 5],
            ],
        ], $overrides);
    }

    #[Test]
    public function 商品編集画面に既存の値が渡される(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Product/Form')
                ->where('product.product_code', 'JS-2026')
                ->has('product.variants', 2)
                ->where('product.variants.0.quantity', 3)
            );
    }

    #[Test]
    public function 組み合わせを減らすと該当のバリエーションと在庫が削除される(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload($product, [
                'variants' => [
                    ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 3],
                ],
            ]),
        );

        $this->assertSame(1, $product->variants()->count());
        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
            'size_name' => 'L',
        ]);
        $this->assertSame(1, Stock::query()->count());
    }

    #[Test]
    public function 組み合わせを増やすとバリエーションと在庫が作られる(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload($product, [
                'variants' => [
                    ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 3],
                    ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => '12', 'is_available' => true, 'quantity' => 5],
                    ['size_name' => 'M', 'color_name' => 'アクア', 'branch_code' => '21', 'is_available' => true, 'quantity' => 7],
                ],
            ]),
        );

        $this->assertSame(3, $product->variants()->count());
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'color_name' => 'アクア',
            'sku_code' => 'JS-2026-21',
        ]);
        $this->assertSame(15, (int) $product->stocks()->sum('quantity'));
    }

    #[Test]
    public function 組み合わせを変えても既存の在庫は保持される(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload($product, [
                'variants' => [
                    ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 3],
                    ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => '12', 'is_available' => true, 'quantity' => 5],
                    ['size_name' => 'S', 'color_name' => 'ネイビー', 'branch_code' => '13', 'is_available' => true, 'quantity' => 0],
                ],
            ]),
        );

        $kept = $product->variants()->where('size_name', 'M')->sole();

        $this->assertSame(3, $kept->stock->quantity);
    }

    #[Test]
    public function 商品識別コードを変えると全てのコードが組み立て直される(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload($product, ['product_code' => 'JS-2027']),
        );

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'branch_code' => '11',
            'sku_code' => 'JS-2027-11',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'branch_code' => '12',
            'sku_code' => 'JS-2027-12',
        ]);
    }

    #[Test]
    public function 枝番を入れ替えても保存できる(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload($product, [
                'variants' => [
                    ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '12', 'is_available' => true, 'quantity' => 3],
                    ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 5],
                ],
            ]),
        );

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size_name' => 'M',
            'sku_code' => 'JS-2026-12',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size_name' => 'L',
            'sku_code' => 'JS-2026-11',
        ]);
    }

    #[Test]
    public function 規格ありから規格なしへ切り替えるとバリエーションが1件に集約される(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload($product, [
                'has_sku' => false,
                'variants' => [
                    ['size_name' => null, 'color_name' => null, 'branch_code' => null, 'is_available' => true, 'quantity' => 9],
                ],
            ]),
        );

        $product->refresh();

        $this->assertFalse($product->has_sku);
        $this->assertSame(1, $product->variants()->count());

        $variant = $product->variants()->sole();
        $this->assertNull($variant->size_name);
        $this->assertSame('JS-2026', $variant->sku_code);
        $this->assertSame(9, $variant->stock->quantity);
    }

    #[Test]
    public function 自身の商品識別コードは重複として扱われない(): void
    {
        $product = $this->makeSkuProduct();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $product), $this->payload($product))
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function 他の商品と同じ商品識別コードには変更できない(): void
    {
        $product = $this->makeSkuProduct();
        Product::factory()->create([
            'product_code' => 'RC7-105',
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(
                route('admin.products.update', $product),
                $this->payload($product, ['product_code' => 'RC7-105']),
            )
            ->assertSessionHasErrors('product_code');
    }

    #[Test]
    public function 未認証は商品を更新できない(): void
    {
        $product = $this->makeSkuProduct();

        $this->put(route('admin.products.update', $product), $this->payload($product))
            ->assertRedirect(route('admin.login'));
    }
}
