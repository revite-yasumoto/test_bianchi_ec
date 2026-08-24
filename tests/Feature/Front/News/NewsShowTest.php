<?php

declare(strict_types=1);

namespace Tests\Feature\Front\News;

use App\Enums\NewsCategory;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ニュースの登録経路は Tests\Feature\Admin\News\NewsStoreTest で検証済みのため、
 * 本テストでは Factory で直接用意する。
 */
class NewsShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 未ログインでも公開中のニュース詳細を閲覧できる(): void
    {
        $news = News::factory()->create([
            'title' => '架空ジャージを発売しました',
            'is_published' => true,
        ]);

        $this->get(route('news.show', $news))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/News/Show')
                ->where('news.id', $news->id)
                ->where('news.title', '架空ジャージを発売しました')
            );
    }

    #[Test]
    public function 本文と種別が詳細に渡される(): void
    {
        $news = News::factory()->create([
            'body' => "1行目の本文\n2行目の本文",
            'category' => NewsCategory::NewProduct,
            'published_on' => '2026-08-13',
            'is_published' => true,
        ]);

        $this->get(route('news.show', $news))
            ->assertInertia(fn ($page) => $page
                ->where('news.body', "1行目の本文\n2行目の本文")
                ->where('news.category', NewsCategory::NewProduct->value)
                ->has('news.category_tone')
                ->where('news.published_on', '2026.08.13')
                ->where('news.published_on_iso', '2026-08-13')
            );
    }

    #[Test]
    public function 非公開のニュース詳細は見つからない(): void
    {
        $news = News::factory()->create([
            'title' => '下書きのニュース',
            'is_published' => false,
        ]);

        $this->get(route('news.show', $news))->assertNotFound();
    }

    #[Test]
    public function 存在しないニュースの詳細は見つからない(): void
    {
        $this->get(route('news.show', 999999))->assertNotFound();
    }
}
