<?php

declare(strict_types=1);

namespace Tests\Feature\Front;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ニュースの登録経路は Tests\Feature\Admin\News\NewsStoreTest で検証済みのため、
 * 本テストでは Factory で直接用意する。
 */
class PaginationLabelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ページ送りのラベルが日本語で渡される(): void
    {
        News::factory()->count(21)->create(['is_published' => true]);

        $this->get(route('news.index'))
            ->assertInertia(fn ($page) => $page
                ->has('news.links', 4)
                ->where('news.links.0.label', '前へ')
                ->where('news.links.3.label', '次へ')
            );
    }
}
