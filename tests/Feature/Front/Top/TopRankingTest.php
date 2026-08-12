<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Top;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRanking;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TopRankingTest extends TestCase
{
    use RefreshDatabase;

    /** 公開済みとして扱われる集計対象月（当日を 2026-08-12 に固定して判定する） */
    private const TARGET_YEAR_MONTH = '2026-07';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);

        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        return $product;
    }

    private function makeRanking(Product $product, int $rank, ?Category $category = null): void
    {
        ProductRanking::factory()->create([
            'target_year_month' => self::TARGET_YEAR_MONTH,
            'category_id' => $category?->id,
            'product_id' => $product->id,
            'rank_position' => $rank,
            'aggregated_at' => '2026-08-01 01:00:00',
        ]);
    }

    #[Test]
    public function 全体タブとカテゴリ別タブが返る(): void
    {
        $category = Category::factory()->create(['name' => 'ロードバイク']);
        $product = $this->makeProduct(['name' => 'ロードスター', 'category_id' => $category->id]);

        $this->makeRanking($product, 1);
        $this->makeRanking($product, 1, $category);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('rankingTabs', 2)
                ->where('rankingTabs.0.key', 'all')
                ->where('rankingTabs.0.label', '全体ランキング')
                ->where('rankingTabs.1.label', 'ロードバイク')
                ->where('rankings.all.0.name', 'ロードスター')
                ->where('rankings.all.0.rank_position', 1)
                ->where('rankingUpdatedAt', '2026.08.01 01:00')
                ->where('rankingUpdatedAtIso', '2026-08-01T01:00:00+09:00')
            );
    }

    #[Test]
    public function 上位四件までに絞られる(): void
    {
        foreach ([1, 2, 3, 4, 5] as $rank) {
            $this->makeRanking($this->makeProduct(['name' => $rank.'位の商品']), $rank);
        }

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('rankings.all', 4)
                ->where('rankings.all.3.name', '4位の商品')
            );
    }

    #[Test]
    public function 非公開になった商品はランキングから除外される(): void
    {
        $this->makeRanking($this->makeProduct(['name' => '非公開商品', 'is_published' => false]), 1);
        $this->makeRanking($this->makeProduct(['name' => '公開商品']), 2);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('rankings.all', 1)
                ->where('rankings.all.0.name', '公開商品')
            );
    }

    #[Test]
    public function ランキングが無いときはタブも空になる(): void
    {
        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('rankingTabs', [])
                ->where('rankings', [])
                ->where('rankingUpdatedAt', null)
            );
    }

    #[Test]
    public function タブは全体を含めて四つまでに絞られ表示しないカテゴリは渡さない(): void
    {
        $product = $this->makeProduct();
        $this->makeRanking($product, 1);

        $categories = [];

        foreach (['ロード', 'MTB', 'パーツ', 'アパレル'] as $index => $name) {
            $category = Category::factory()->create(['name' => $name, 'sort_order' => $index]);
            $categories[] = $category;
            $this->makeRanking($this->makeProduct(['category_id' => $category->id]), 1, $category);
        }

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('rankingTabs', 4)
                ->has('rankings', 4)
                ->missing('rankings.'.$categories[3]->id)
            );
    }
}
