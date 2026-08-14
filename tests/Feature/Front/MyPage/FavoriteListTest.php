<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FavoriteListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = [], int $quantity = 3): Product
    {
        $product = Product::factory()->create($attributes);

        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create(['product_variant_id' => $variant->id, 'quantity' => $quantity]);

        return $product;
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('mypage.favorites'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 自分のお気に入りだけが表示される(): void
    {
        $mine = $this->createProduct(['name' => '架空ジャージ']);
        $others = $this->createProduct(['name' => '他人のお気に入り']);

        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $mine->id]);
        Favorite::factory()->create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $others->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/MyPage/Favorites')
                ->has('products', 1)
                ->where('products.0.name', '架空ジャージ')
            );
    }

    #[Test]
    public function 非公開の商品は表示されない(): void
    {
        $product = $this->createProduct(['is_published' => false]);
        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $product->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertInertia(fn ($page) => $page->has('products', 0));
    }

    #[Test]
    public function 在庫のある商品は在庫切れとして表示されない(): void
    {
        $product = $this->createProduct(['name' => '架空ジャージ'], quantity: 3);
        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $product->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertInertia(fn ($page) => $page->where('products.0.is_sold_out', false));
    }

    #[Test]
    public function 在庫切れの商品は在庫切れとして表示される(): void
    {
        $product = $this->createProduct(['name' => '架空ヘルメット'], quantity: 0);
        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $product->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertInertia(fn ($page) => $page->where('products.0.is_sold_out', true));
    }

    #[Test]
    public function 登録が新しい順に並ぶ(): void
    {
        $first = $this->createProduct(['name' => '先に登録した商品']);
        $second = $this->createProduct(['name' => '後に登録した商品']);

        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $first->id]);
        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $second->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertInertia(fn ($page) => $page
                ->where('products.0.name', '後に登録した商品')
                ->where('products.1.name', '先に登録した商品')
            );
    }

    #[Test]
    public function お気に入りを解除すると一覧から消える(): void
    {
        $product = $this->createProduct();
        Favorite::factory()->create(['user_id' => $this->user->id, 'product_id' => $product->id]);

        $this->actingAs($this->user)
            ->from(route('mypage.favorites'))
            ->delete(route('favorites.destroy', [$product]))
            ->assertRedirect(route('mypage.favorites'));

        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertInertia(fn ($page) => $page->has('products', 0));
    }

    #[Test]
    public function お気に入りが無いときは空で返る(): void
    {
        $this->actingAs($this->user)
            ->get(route('mypage.favorites'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('products', 0));
    }
}
