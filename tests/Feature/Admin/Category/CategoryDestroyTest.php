<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Category;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryDestroyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 商品が登録されていないカテゴリは削除できる(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * 画像を持つカテゴリは Factory と偽のストレージで用意する。画像を保存する経路は CategoryImageTest が検証している。
     */
    #[Test]
    public function カテゴリを削除すると画像ファイルも削除される(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('categories/helmet.jpg', 'image');
        $admin = Admin::factory()->create();
        $category = Category::factory()->create(['image_path' => 'categories/helmet.jpg']);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        Storage::disk('public')->assertMissing('categories/helmet.jpg');
    }

    #[Test]
    public function 商品が登録されているカテゴリは削除できずエラーになる(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.categories.destroy', $category));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[Test]
    public function 未認証はカテゴリを削除できない(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
