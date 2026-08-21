<?php

declare(strict_types=1);

namespace Tests\Feature\Front\News;

use App\Enums\NewsCategory;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 未ログインでも新着ニュースを閲覧できる(): void
    {
        News::factory()->create([
            'title' => '架空ジャージを発売しました',
            'is_published' => true,
        ]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/News/Index')
                ->has('news.data', 1)
                ->where('news.data.0.title', '架空ジャージを発売しました')
            );
    }

    #[Test]
    public function 非公開のニュースは表示されない(): void
    {
        News::factory()->create(['title' => '公開中のニュース', 'is_published' => true]);
        News::factory()->create(['title' => '下書きのニュース', 'is_published' => false]);

        $this->get(route('news.index'))
            ->assertInertia(fn ($page) => $page
                ->has('news.data', 1)
                ->where('news.data.0.title', '公開中のニュース')
            );
    }

    #[Test]
    public function 掲載日の新しい順に並ぶ(): void
    {
        News::factory()->create([
            'title' => '古いニュース',
            'published_on' => '2026-08-01',
            'is_published' => true,
        ]);
        News::factory()->create([
            'title' => '新しいニュース',
            'published_on' => '2026-08-10',
            'is_published' => true,
        ]);

        $this->get(route('news.index'))
            ->assertInertia(fn ($page) => $page
                ->where('news.data.0.title', '新しいニュース')
                ->where('news.data.1.title', '古いニュース')
            );
    }

    #[Test]
    public function 種別が一覧に渡される(): void
    {
        News::factory()->create([
            'title' => '架空のお知らせ',
            'category' => NewsCategory::NewProduct,
            'is_published' => true,
        ]);

        $this->get(route('news.index'))
            ->assertInertia(fn ($page) => $page
                ->where('news.data.0.category', NewsCategory::NewProduct->value)
                ->has('news.data.0.category_tone')
            );
    }

    #[Test]
    public function 本文は一覧に渡されない(): void
    {
        News::factory()->create([
            'body' => '一覧には出さない本文',
            'is_published' => true,
        ]);

        $this->get(route('news.index'))
            ->assertInertia(fn ($page) => $page->missing('news.data.0.body'));
    }

    #[Test]
    public function 一ページあたり二十件までで区切られる(): void
    {
        News::factory()->count(21)->create(['is_published' => true]);

        $this->get(route('news.index'))
            ->assertInertia(fn ($page) => $page
                ->has('news.data', 20)
                ->where('news.total', 21)
            );
    }

    #[Test]
    public function 公開中のニュースが無くても閲覧できる(): void
    {
        $this->get(route('news.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('news.data', 0));
    }
}
