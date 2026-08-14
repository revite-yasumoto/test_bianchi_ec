<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Category;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function カテゴリ管理画面に登録商品件数付きの一覧が表示される(): void
    {
        $admin = Admin::factory()->create();
        $helmet = Category::factory()->create(['name' => 'ヘルメット', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'ウェア', 'sort_order' => 2]);
        Product::factory()->count(2)->create(['category_id' => $helmet->id]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.categories.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Category/Index')
                ->has('categories', 2)
                ->where('categories.0.name', 'ヘルメット')
                ->where('categories.0.product_count', 2)
                ->where('categories.1.name', 'ウェア')
                ->where('categories.1.product_count', 0)
            );
    }

    #[Test]
    public function 一覧は表示順の昇順で並ぶ(): void
    {
        $admin = Admin::factory()->create();
        Category::factory()->create(['name' => '後', 'sort_order' => 5]);
        Category::factory()->create(['name' => '先', 'sort_order' => 1]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.categories.index'))
            ->assertInertia(fn ($page) => $page
                ->where('categories.0.name', '先')
                ->where('categories.1.name', '後')
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.login'));
    }
}
