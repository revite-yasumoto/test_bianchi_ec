<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Category;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function カテゴリを追加できる(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => 'ヘルメット']);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'ヘルメット']);
    }

    #[Test]
    public function 追加したカテゴリの表示順は既存の最大値の次になる(): void
    {
        $admin = Admin::factory()->create();
        Category::factory()->create(['sort_order' => 7]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => 'ヘルメット']);

        $this->assertDatabaseHas('categories', ['name' => 'ヘルメット', 'sort_order' => 8]);
    }

    #[Test]
    public function 同名のカテゴリは追加できない(): void
    {
        $admin = Admin::factory()->create();
        Category::factory()->create(['name' => 'ヘルメット']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => 'ヘルメット']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Category::query()->where('name', 'ヘルメット')->count());
    }

    #[Test]
    public function カテゴリ名が未入力では追加できない(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function カテゴリ名が50文字を超えると追加できない(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => str_repeat('あ', 51)]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('categories', 0);
    }

    #[Test]
    public function 未認証はカテゴリを追加できない(): void
    {
        $this->post(route('admin.categories.store'), ['name' => 'ヘルメット'])
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('categories', 0);
    }
}
