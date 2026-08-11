<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\News;

use App\Models\Admin;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsDestroyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ニュースを削除できる(): void
    {
        $admin = Admin::factory()->create();
        $news = News::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.news.destroy', [$news->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('news', 0);
    }

    #[Test]
    public function 未認証は削除できない(): void
    {
        $news = News::factory()->create();

        $this->delete(route('admin.news.destroy', [$news->id]))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('news', 1);
    }
}
