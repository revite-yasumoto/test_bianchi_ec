<?php

declare(strict_types=1);

namespace Tests\Feature\Front;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 共有データの会員情報は識別子・会員番号・氏名のみを含む(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.member_code', $user->member_code)
                ->where('auth.user.name', $user->name)
                ->missing('auth.user.email')
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
                ->missing('auth.user.tel')
                ->missing('auth.user.status')
            );
    }

    #[Test]
    public function 未ログイン時の共有データは会員情報を持たず件数が0になる(): void
    {
        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user', null)
                ->where('cartCount', 0)
                ->where('favoriteCount', 0)
            );
    }

    #[Test]
    public function カート件数は数量の合計・お気に入り件数は明細数で共有される(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variants = ProductVariant::factory()->count(2)->create([
            'product_id' => $product->id,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variants[0]->id,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variants[1]->id,
            'quantity' => 3,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('cartCount', 5)
                ->where('favoriteCount', 1)
            );
    }

    #[Test]
    public function カートドロワー用の明細が共有される(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'アパレル']);
        $product = Product::factory()->create([
            'name' => 'チームジャージ',
            'price' => 14800,
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => 'M',
            'color_name' => 'レッド',
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('cartItems', 1)
                ->where('cartItems.0.name', 'チームジャージ')
                ->where('cartItems.0.variant_label', 'レッド / M')
                ->where('cartItems.0.quantity', 2)
                ->where('cartItems.0.line_total', 29600)
                ->where('cartItems.0.category_name', 'アパレル')
            );
    }

    #[Test]
    public function 管理画面の共有データはフロントのパスに現れない(): void
    {
        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->missing('auth.admin'));
    }
}
