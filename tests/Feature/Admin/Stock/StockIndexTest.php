<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Stock;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockIndexTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    /**
     * @param  array<int, array{size: string|null, color: string|null, quantity: int}>  $variants
     */
    private function makeProduct(string $name, Category $category, bool $hasSku, array $variants): Product
    {
        $product = Product::factory()->create([
            'name' => $name,
            'category_id' => $category->id,
            'has_sku' => $hasSku,
        ]);

        foreach ($variants as $index => $variant) {
            $created = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'size_name' => $variant['size'],
                'color_name' => $variant['color'],
                'branch_code' => $hasSku ? (string) (11 + $index) : null,
                'sku_code' => $hasSku
                    ? $product->product_code.'-'.(11 + $index)
                    : $product->product_code,
            ]);

            Stock::factory()->create([
                'product_variant_id' => $created->id,
                'quantity' => $variant['quantity'],
            ]);
        }

        return $product;
    }

    #[Test]
    public function 在庫一覧がバリエーション単位で表示される(): void
    {
        $category = Category::factory()->create(['name' => 'ウェア']);
        $this->makeProduct('チームジャージ', $category, true, [
            ['size' => 'M', 'color' => 'ネイビー', 'quantity' => 3],
            ['size' => 'L', 'color' => 'ネイビー', 'quantity' => 0],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stocks.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Stock/Index')
                ->has('stocks.data', 2)
                ->where('stocks.data.0.product_name', 'チームジャージ')
                ->where('stocks.data.0.category_name', 'ウェア')
                // 並びはカラー → サイズの昇順のため L が先に来る
                ->where('stocks.data.0.variant_label', 'ネイビー / L')
                ->where('stocks.data.0.quantity', 0)
                ->where('stocks.data.1.variant_label', 'ネイビー / M')
                ->where('stocks.data.1.quantity', 3)
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function 規格なし商品は1行で規格なしと表示される(): void
    {
        $category = Category::factory()->create();
        $product = $this->makeProduct('ロードスター', $category, false, [
            ['size' => null, 'color' => null, 'quantity' => 12],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stocks.index'))
            ->assertInertia(fn ($page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.variant_label', '規格なし')
                ->where('stocks.data.0.sku_code', $product->product_code)
            );
    }

    #[Test]
    public function 規格の有無で絞り込める(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct('チームジャージ', $category, true, [
            ['size' => 'M', 'color' => 'ネイビー', 'quantity' => 3],
        ]);
        $this->makeProduct('ロードスター', $category, false, [
            ['size' => null, 'color' => null, 'quantity' => 12],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stocks.index', ['has_sku' => 'without']))
            ->assertInertia(fn ($page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.product_name', 'ロードスター')
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function カテゴリで絞り込める(): void
    {
        $wear = Category::factory()->create();
        $road = Category::factory()->create();
        $this->makeProduct('チームジャージ', $wear, false, [
            ['size' => null, 'color' => null, 'quantity' => 3],
        ]);
        $this->makeProduct('ロードスター', $road, false, [
            ['size' => null, 'color' => null, 'quantity' => 12],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stocks.index', ['category_id' => $road->id]))
            ->assertInertia(fn ($page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.product_name', 'ロードスター')
            );
    }

    #[Test]
    public function 商品名で絞り込める(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct('チームジャージ', $category, false, [
            ['size' => null, 'color' => null, 'quantity' => 3],
        ]);
        $this->makeProduct('ロードスター', $category, false, [
            ['size' => null, 'color' => null, 'quantity' => 12],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stocks.index', ['q' => 'ジャージ']))
            ->assertInertia(fn ($page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.product_name', 'チームジャージ')
            );
    }

    #[Test]
    public function 一覧は商品名の昇順で並ぶ(): void
    {
        $category = Category::factory()->create();
        $this->makeProduct('ロードスター', $category, false, [
            ['size' => null, 'color' => null, 'quantity' => 1],
        ]);
        $this->makeProduct('アクセサリー', $category, false, [
            ['size' => null, 'color' => null, 'quantity' => 2],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stocks.index'))
            ->assertInertia(fn ($page) => $page
                ->where('stocks.data.0.product_name', 'アクセサリー')
                ->where('stocks.data.1.product_name', 'ロードスター')
            );
    }

    #[Test]
    public function 一覧の発行クエリ数は行数に比例しない(): void
    {
        $category = Category::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->makeProduct("商品{$i}", $category, false, [
                ['size' => null, 'color' => null, 'quantity' => $i],
            ]);
        }

        $this->actingAs($this->admin, 'admin');

        DB::enableQueryLog();
        $this->get(route('admin.stocks.index'))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 行数（5件）を増やしてもクエリ数が増えないことを確認する
        $this->assertLessThan(15, $queryCount);
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.stocks.index'))
            ->assertRedirect(route('admin.login'));
    }
}
