<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\News;

use App\Enums\NewsCategory;
use App\Models\Admin;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ニュースを更新できる(): void
    {
        $admin = Admin::factory()->create();
        $news = News::factory()->create([
            'title' => '変更前のタイトル',
            'category' => NewsCategory::NewProduct,
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(
            route('admin.news.update', [$news->id]),
            [
                'published_on' => '2026-08-20',
                'category' => 'お知らせ',
                'title' => '変更後のタイトル',
                'body' => '更新した本文',
                'is_published' => false,
            ],
        );

        $response->assertSessionHasNoErrors();

        $news->refresh();
        $this->assertSame('変更後のタイトル', $news->title);
        $this->assertSame('お知らせ', $news->category->value);
        $this->assertSame('2026-08-20', $news->published_on->toDateString());
        $this->assertFalse($news->is_published);
    }

    #[Test]
    public function 不正な値では更新されない(): void
    {
        $admin = Admin::factory()->create();
        $news = News::factory()->create(['title' => '変更前のタイトル']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.news.update', [$news->id]), [
                'published_on' => '2026-08-20',
                'category' => '新商品',
                'title' => '',
                'body' => '更新した本文',
                'is_published' => true,
            ])
            ->assertSessionHasErrors('title');

        $this->assertSame('変更前のタイトル', $news->refresh()->title);
    }

    #[Test]
    public function 未認証は更新できない(): void
    {
        $news = News::factory()->create(['title' => '変更前のタイトル']);

        $this->put(route('admin.news.update', [$news->id]), [
            'published_on' => '2026-08-20',
            'category' => '新商品',
            'title' => '変更後のタイトル',
            'body' => '更新した本文',
            'is_published' => true,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame('変更前のタイトル', $news->refresh()->title);
    }
}
