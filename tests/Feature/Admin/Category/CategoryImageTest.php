<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Category;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesImageUpload;
use Tests\TestCase;

/**
 * 画像を持つ既存カテゴリは Factory と偽のストレージで用意する。画像を保存する経路は「カテゴリに画像を登録できる」が検証している。
 */
class CategoryImageTest extends TestCase
{
    use FakesImageUpload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function categoryWithImage(): Category
    {
        Storage::disk('public')->put('categories/old.jpg', 'old');

        return Category::factory()->create(['image_path' => 'categories/old.jpg']);
    }

    #[Test]
    public function カテゴリに画像を登録できる(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.categories.index'))
            ->put(route('admin.categories.image.update', $category), [
                'image' => $this->fakeImage('road.webp'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    #[Test]
    public function 画像を差し替えると元の画像ファイルが削除される(): void
    {
        $admin = Admin::factory()->create();
        $category = $this->categoryWithImage();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.categories.image.update', $category), [
                'image' => $this->fakeImage('new.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertNotSame('categories/old.jpg', $category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
        Storage::disk('public')->assertMissing('categories/old.jpg');
    }

    #[Test]
    public function カテゴリの画像を削除できる(): void
    {
        $admin = Admin::factory()->create();
        $category = $this->categoryWithImage();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.image.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertNull($category->refresh()->image_path);
        Storage::disk('public')->assertMissing('categories/old.jpg');
    }

    #[Test]
    public function 画像が未選択では登録できない(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.categories.image.update', $category), [])
            ->assertSessionHasErrors('image');

        $this->assertNull($category->refresh()->image_path);
    }

    #[Test]
    public function 許可されていない形式の画像は登録できない(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.categories.image.update', $category), [
                'image' => UploadedFile::fake()->create('road.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertNull($category->refresh()->image_path);
    }

    #[Test]
    public function 未認証はカテゴリの画像を変更できない(): void
    {
        $category = $this->categoryWithImage();

        $this->put(route('admin.categories.image.update', $category), [
            'image' => $this->fakeImage('new.jpg'),
        ])->assertRedirect(route('admin.login'));

        $this->delete(route('admin.categories.image.destroy', $category))
            ->assertRedirect(route('admin.login'));

        $this->assertSame('categories/old.jpg', $category->refresh()->image_path);
        Storage::disk('public')->assertExists('categories/old.jpg');
    }
}
