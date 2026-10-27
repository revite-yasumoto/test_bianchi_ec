<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Top;

use App\Enums\BannerBackground;
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
    public function バナーに紐づく公開商品が遷移先として返る(): void
    {
        $product = $this->makeProduct();
        Banner::factory()->create(['product_id' => $product->id]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('banners.0.product_id', $product->id)
            );
    }

    #[Test]
    public function バナーに紐づく商品が非公開なら遷移先として返らない(): void
    {
        $product = $this->makeProduct(['is_published' => false]);
        Banner::factory()->create(['product_id' => $product->id]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('banners.0.product_id', null)
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

    /**
     * 画像パスは Factory で用意する。画像を保存する経路は Admin\Category\CategoryImageTest が検証している。
     */
    #[Test]
    public function カテゴリ入口に登録した画像のアドレスが付く(): void
    {
        Category::factory()->create(['sort_order' => 1, 'image_path' => 'categories/road.jpg']);
        Category::factory()->create(['sort_order' => 2]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('categoryEntries.0.image_url', asset('storage/categories/road.jpg'))
                ->where('categoryEntries.1.image_url', null)
            );
    }

    /**
     * 背景は Factory で用意する。背景を保存して作成する経路は Admin\Banner\BannerStoreTest が検証している。
     */
    #[Test]
    public function バナーの背景は配色の値で返す(): void
    {
        Banner::factory()->create(['background' => BannerBackground::Coral]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('banners.0.background', 'linear-gradient(115deg,#E1664B 0%,#b64530 55%,#7d2d1e 100%)')
            );
    }

    /**
     * 画像パスは Factory で用意する。画像を保存して作成する経路は Admin\Banner\BannerStoreTest が検証している。
     */
    #[Test]
    public function バナーの画像はパソコン用とスマホ用を別々に返す(): void
    {
        Banner::factory()->create(['sort_order' => 0, 'pc_image_path' => 'banners/a-pc.jpg', 'sp_image_path' => 'banners/a-sp.jpg']);
        Banner::factory()->create(['sort_order' => 1, 'pc_image_path' => 'banners/b-pc.jpg']);
        Banner::factory()->create(['sort_order' => 2, 'sp_image_path' => 'banners/c-sp.jpg']);
        Banner::factory()->create(['sort_order' => 3]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('banners.0.pc_image_url', asset('storage/banners/a-pc.jpg'))
                ->where('banners.0.sp_image_url', asset('storage/banners/a-sp.jpg'))
                ->where('banners.1.pc_image_url', asset('storage/banners/b-pc.jpg'))
                ->where('banners.1.sp_image_url', null)
                ->where('banners.2.pc_image_url', null)
                ->where('banners.2.sp_image_url', asset('storage/banners/c-sp.jpg'))
                ->where('banners.3.pc_image_url', null)
                ->where('banners.3.sp_image_url', null)
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
