<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = [], int $quantity = 3): Product
    {
        $product = Product::factory()->create($attributes);

        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);

        return $product;
    }

    #[Test]
    public function 未ログインでも公開商品の一覧が表示される(): void
    {
        $category = Category::factory()->create(['name' => 'ロードバイク']);
        $this->makeProduct([
            'name' => 'ロードスター',
            'product_code' => 'RC7-105',
            'price' => 398000,
            'category_id' => $category->id,
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Product/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'ロードスター')
                ->where('products.data.0.category_name', 'ロードバイク')
                ->where('products.data.0.price', 398000)
                ->where('products.data.0.is_sold_out', false)
                ->where('totalCount', 1)
            );
    }

    #[Test]
    public function 非公開商品は一覧に表示されない(): void
    {
        $this->makeProduct(['name' => '公開商品', 'is_published' => true]);
        $this->makeProduct(['name' => '非公開商品', 'is_published' => false]);

        $this->get(route('products.index'))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', '公開商品')
                ->where('totalCount', 1)
            );
    }

    #[Test]
    public function カテゴリで絞り込める(): void
    {
        $road = Category::factory()->create(['name' => 'ロードバイク']);
        $wear = Category::factory()->create(['name' => 'アパレル']);
        $this->makeProduct(['name' => 'ロードスター', 'category_id' => $road->id]);
        $this->makeProduct(['name' => 'チームジャージ', 'category_id' => $wear->id]);

        $this->get(route('products.index', ['category_id' => $wear->id]))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'チームジャージ')
                ->where('filters.category_id', $wear->id)
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function 価格帯で絞り込める(): void
    {
        $this->makeProduct(['name' => 'アパレル', 'price' => 9900]);
        $this->makeProduct(['name' => '車体', 'price' => 398000]);

        $this->get(route('products.index', ['price_range' => 'under_10000']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'アパレル')
                ->where('filters.price_range', 'under_10000')
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function 価格帯の境界は下限を含み上限を含まない(): void
    {
        $this->makeProduct(['name' => '下限ちょうど', 'price' => 10000]);
        $this->makeProduct(['name' => '上限ちょうど', 'price' => 50000]);

        $this->get(route('products.index', ['price_range' => 'from_10000']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', '下限ちょうど')
            );
    }

    #[Test]
    public function 上限のない価格帯は下限以上の商品が残る(): void
    {
        $this->makeProduct(['name' => '高額商品', 'price' => 980000]);
        $this->makeProduct(['name' => '中価格帯', 'price' => 149999]);

        $this->get(route('products.index', ['price_range' => 'from_150000']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', '高額商品')
            );
    }

    #[Test]
    public function カテゴリと価格帯を併用できる(): void
    {
        $wear = Category::factory()->create(['name' => 'アパレル']);
        $bike = Category::factory()->create(['name' => 'ロードバイク']);
        $this->makeProduct(['name' => '安いジャージ', 'price' => 9900, 'category_id' => $wear->id]);
        $this->makeProduct(['name' => '高いジャージ', 'price' => 39900, 'category_id' => $wear->id]);
        $this->makeProduct(['name' => '安い車体', 'price' => 9900, 'category_id' => $bike->id]);

        $this->get(route('products.index', [
            'category_id' => $wear->id,
            'price_range' => 'under_10000',
        ]))->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', '安いジャージ')
        );
    }

    #[Test]
    public function 存在しない価格帯の値は絞り込みに使われない(): void
    {
        $this->makeProduct(['price' => 9900]);
        $this->makeProduct(['price' => 398000]);

        $this->get(route('products.index', ['price_range' => 'unknown-range']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 2)
                ->where('filters.price_range', null)
            );
    }

    #[Test]
    public function 在庫が0の商品は在庫切れとして返る(): void
    {
        $this->makeProduct(['name' => '在庫切れ商品'], quantity: 0);

        $this->get(route('products.index'))
            ->assertInertia(fn ($page) => $page
                ->where('products.data.0.is_sold_out', true)
            );
    }

    #[Test]
    public function カテゴリの選択肢が並び順で返る(): void
    {
        Category::factory()->create(['name' => 'アパレル', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'ロードバイク', 'sort_order' => 1]);

        $this->get(route('products.index'))
            ->assertInertia(fn ($page) => $page
                ->where('categories.0.name', 'ロードバイク')
                ->where('categories.1.name', 'アパレル')
            );
    }

    #[Test]
    public function 商品件数が増えてもクエリ数が増えない(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct(['category_id' => $category->id]);

        $queryCount = $this->countQueriesOfIndex();

        for ($index = 0; $index < 5; $index++) {
            $this->makeProduct(['category_id' => $category->id]);
        }

        $this->assertSame($queryCount, $this->countQueriesOfIndex());
    }

    #[Test]
    public function 商品画像のリンク先はアクセス元のホストとポートに追従する(): void
    {
        $product = $this->makeProduct();
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/1/sample.png',
            'sort_order' => 0,
        ]);

        // APP_URL の設定値ではなくリクエストを基準にする。ポートを変えた環境でも画像が解決できる必要がある
        $this->get('http://localhost:8000/products')
            ->assertInertia(fn ($page) => $page->where(
                'products.data.0.main_image_url',
                'http://localhost:8000/storage/products/1/sample.png',
            ));
    }

    private function countQueriesOfIndex(): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->get(route('products.index'))->assertOk();

        return $count;
    }
}
