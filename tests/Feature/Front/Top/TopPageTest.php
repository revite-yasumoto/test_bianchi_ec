<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Top;

use App\Models\Banner;
use App\Models\BrowsingHistory;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TopPageTest extends TestCase
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
    public function 未ログインでもトップページが表示される(): void
    {
        $banner = Banner::factory()->create(['title' => 'キャンペーン']);
        $this->makeProduct(['name' => 'ロードスター']);

        $this->get(route('top'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Top/Index')
                ->has('banners', 1)
                ->where('banners.0.id', $banner->id)
                ->where('banners.0.title', 'キャンペーン')
                ->has('recommends', 1)
                ->where('recommends.0.name', 'ロードスター')
                ->where('histories', [])
            );
    }

    #[Test]
    public function 非公開のバナーは表示されない(): void
    {
        Banner::factory()->create(['title' => '公開バナー', 'is_active' => true]);
        Banner::factory()->create(['title' => '非公開バナー', 'is_active' => false]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('banners', 1)
                ->where('banners.0.title', '公開バナー')
            );
    }

    #[Test]
    public function おすすめは公開商品の新着順で四件まで返る(): void
    {
        $this->makeProduct(['is_published' => false, 'name' => '非公開商品']);

        foreach (['一つ目', '二つ目', '三つ目', '四つ目', '五つ目'] as $name) {
            $this->makeProduct(['name' => $name]);
        }

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('recommends', 4)
                ->where('recommends.0.name', '五つ目')
                ->where('recommends.3.name', '二つ目')
            );
    }

    #[Test]
    public function カテゴリ入口に公開商品の件数が付く(): void
    {
        $category = Category::factory()->create(['name' => 'ロードバイク', 'sort_order' => 1]);
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id, 'is_published' => false]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('categoryEntries.0.name', 'ロードバイク')
                ->where('categoryEntries.0.product_count', 1)
            );
    }

    #[Test]
    public function ログイン中は閲覧履歴が新しい順で返る(): void
    {
        $user = User::factory()->create();
        $older = $this->makeProduct(['name' => '先に見た商品']);
        $newer = $this->makeProduct(['name' => '後に見た商品']);

        BrowsingHistory::factory()->create([
            'user_id' => $user->id,
            'product_id' => $older->id,
            'viewed_at' => now()->subHour(),
        ]);
        BrowsingHistory::factory()->create([
            'user_id' => $user->id,
            'product_id' => $newer->id,
            'viewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('histories', 2)
                ->where('histories.0.name', '後に見た商品')
                ->where('histories.1.name', '先に見た商品')
            );
    }

    #[Test]
    public function 非公開になった商品は閲覧履歴に出さない(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['is_published' => false]);

        BrowsingHistory::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('histories', []));
    }

    #[Test]
    public function 非公開商品があっても閲覧履歴の表示件数が減らない(): void
    {
        $user = User::factory()->create();

        BrowsingHistory::factory()->create([
            'user_id' => $user->id,
            'product_id' => $this->makeProduct(['is_published' => false])->id,
            'viewed_at' => now(),
        ]);

        foreach (range(1, 6) as $index) {
            BrowsingHistory::factory()->create([
                'user_id' => $user->id,
                'product_id' => $this->makeProduct()->id,
                'viewed_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($user)
            ->get(route('top'))
            ->assertInertia(fn ($page) => $page->has('histories', 6));
    }

    #[Test]
    public function 商品件数が増えてもクエリ数が増えない(): void
    {
        $user = User::factory()->create();

        for ($index = 0; $index < 2; $index++) {
            $product = $this->makeProduct();
            BrowsingHistory::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        $queryCount = $this->countQueriesOfTop($user);

        for ($index = 0; $index < 4; $index++) {
            $product = $this->makeProduct();
            BrowsingHistory::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        $this->assertSame($queryCount, $this->countQueriesOfTop($user));
    }

    private function countQueriesOfTop(User $user): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->actingAs($user)->get(route('top'))->assertOk();

        return $count;
    }
}
