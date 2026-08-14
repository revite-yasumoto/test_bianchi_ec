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

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    private function makeProduct(Category $category, string $code, string $name, int $price, bool $hasSku = false): Product
    {
        return Product::factory()->create([
            'product_code' => $code,
            'name' => $name,
            'category_id' => $category->id,
            'price' => $price,
            'has_sku' => $hasSku,
        ]);
    }

    private function stockOf(Product $product, int $quantity): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => null,
            'color_name' => null,
        ]);

        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 商品一覧が表示され在庫合計が算出される(): void
    {
        $category = Category::factory()->create(['name' => 'ロードバイク']);
        $product = $this->makeProduct($category, 'RC7-105', 'ロードスター', 398000);
        $this->stockOf($product, 4);
        $this->stockOf($product, 6);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Product/Index')
                ->has('products.data', 1)
                ->where('products.data.0.product_code', 'RC7-105')
                ->where('products.data.0.category_name', 'ロードバイク')
                ->where('products.data.0.total_stock', 10)
                ->where('totalCount', 1)
            );
    }

    #[Test]
    public function 商品名で絞り込める(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct($category, 'RC7-105', 'ロードスター', 398000);
        $this->makeProduct($category, 'MT3-STD', 'トレイルヘッド', 198000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['q' => 'ロードスター']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'ロードスター')
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function 商品識別コードでも絞り込める(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct($category, 'RC7-105', 'ロードスター', 398000);
        $this->makeProduct($category, 'MT3-STD', 'トレイルヘッド', 198000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['q' => 'MT3']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.product_code', 'MT3-STD')
            );
    }

    #[Test]
    public function カテゴリで絞り込める(): void
    {
        $road = Category::factory()->create(['name' => 'ロードバイク']);
        $wear = Category::factory()->create(['name' => 'ウェア']);
        $this->makeProduct($road, 'RC7-105', 'ロードスター', 398000);
        $this->makeProduct($wear, 'JS-2026', 'チームジャージ', 12000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['category_id' => $wear->id]))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'チームジャージ')
            );
    }

    #[Test]
    public function 規格の有無で絞り込める(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct($category, 'RC7-105', 'ロードスター', 398000, false);
        $this->makeProduct($category, 'JS-2026', 'チームジャージ', 12000, true);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['has_sku' => 'with']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'チームジャージ')
            );

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['has_sku' => 'without']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'ロードスター')
            );
    }

    #[Test]
    public function 価格帯で絞り込める(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct($category, 'A-1', '安価', 5000);
        $this->makeProduct($category, 'B-1', '中価格', 50000);
        $this->makeProduct($category, 'C-1', '高価格', 500000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['price_min' => 10000, 'price_max' => 100000]))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', '中価格')
            );
    }

    #[Test]
    public function 複数の条件を組み合わせて絞り込める(): void
    {
        $road = Category::factory()->create();
        $wear = Category::factory()->create();
        $this->makeProduct($road, 'RC7-105', 'ロードスター', 398000, true);
        $this->makeProduct($wear, 'JS-2026', 'ロードジャージ', 12000, true);
        $this->makeProduct($wear, 'JS-2027', 'ロードジャージ 長袖', 15000, false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', [
                'q' => 'ロード',
                'category_id' => $wear->id,
                'has_sku' => 'with',
            ]))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.product_code', 'JS-2026')
                ->where('totalCount', 3)
            );
    }

    #[Test]
    public function 該当がない場合は空の一覧になる(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct($category, 'RC7-105', 'ロードスター', 398000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['q' => '存在しない商品']))
            ->assertInertia(fn ($page) => $page->has('products.data', 0));
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }
}
