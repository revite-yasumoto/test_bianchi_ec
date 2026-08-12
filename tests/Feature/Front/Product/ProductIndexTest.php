<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Product;

use App\Models\Category;
use App\Models\Product;
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
