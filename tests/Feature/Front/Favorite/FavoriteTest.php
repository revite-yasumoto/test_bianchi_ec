<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Favorite;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function お気に入りに登録できる(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->post(route('favorites.store'), ['product_id' => $product->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function お気に入りを解除できる(): void
    {
        $product = Product::factory()->create();
        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('favorites.destroy', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('favorites', 0);
    }

    #[Test]
    public function 同じ商品を二重に登録しても行は増えない(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->post(route('favorites.store'), ['product_id' => $product->id]);
        $this->actingAs($this->user)
            ->post(route('favorites.store'), ['product_id' => $product->id]);

        $this->assertDatabaseCount('favorites', 1);
    }

    #[Test]
    public function 他の会員のお気に入りは解除できない(): void
    {
        $product = Product::factory()->create();
        $other = User::factory()->create();
        Favorite::factory()->create([
            'user_id' => $other->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('favorites.destroy', $product));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $other->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function 非公開商品はお気に入りに登録できない(): void
    {
        $product = Product::factory()->create(['is_published' => false]);

        $this->actingAs($this->user)
            ->post(route('favorites.store'), ['product_id' => $product->id])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('favorites', 0);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $product = Product::factory()->create();

        $this->post(route('favorites.store'), ['product_id' => $product->id])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }
}
