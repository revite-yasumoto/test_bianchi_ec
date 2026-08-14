<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Top;

use App\Enums\NewsCategory;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TopNewsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 公開ニュースが新しい順に四件まで返る(): void
    {
        foreach ([1, 2, 3, 4, 5] as $daysAgo) {
            News::factory()->create([
                'title' => $daysAgo.'日前のニュース',
                'published_on' => now()->subDays($daysAgo)->toDateString(),
            ]);
        }

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('news', 4)
                ->where('news.0.title', '1日前のニュース')
                ->where('news.3.title', '4日前のニュース')
            );
    }

    #[Test]
    public function 非公開ニュースは返らない(): void
    {
        News::factory()->create(['title' => '公開ニュース', 'is_published' => true]);
        News::factory()->create(['title' => '非公開ニュース', 'is_published' => false]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->has('news', 1)
                ->where('news.0.title', '公開ニュース')
            );
    }

    #[Test]
    public function 種別と配色が渡る(): void
    {
        News::factory()->create([
            'title' => '新商品のお知らせ',
            'category' => NewsCategory::NewProduct,
            'published_on' => '2026-08-01',
        ]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('news.0.published_on', '2026.08.01')
                ->where('news.0.published_on_iso', '2026-08-01')
                ->where('news.0.category', '新商品')
                ->where('news.0.category_tone.fg', '#2F6F86')
                ->where('news.0.category_tone.bg', '#E7F0F4')
            );
    }
}
