<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\News;

use App\Enums\NewsCategory;
use App\Models\Admin;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 公開と非公開の両方が一覧に表示される(): void
    {
        $admin = Admin::factory()->create();
        News::factory()->create([
            'title' => '公開中のニュース',
            'published_on' => '2026-08-10',
            'is_published' => true,
        ]);
        News::factory()->create([
            'title' => '下書きのニュース',
            'published_on' => '2026-08-09',
            'is_published' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.news.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/News/Index')
                ->has('news.data', 2)
                ->where('news.data.0.title', '公開中のニュース')
                ->where('news.data.0.state_label', '公開')
                ->where('news.data.1.title', '下書きのニュース')
                ->where('news.data.1.state_label', '非公開')
            );
    }

    #[Test]
    public function 一覧は掲載日の降順で並ぶ(): void
    {
        $admin = Admin::factory()->create();
        News::factory()->create(['title' => '古い', 'published_on' => '2026-07-01']);
        News::factory()->create(['title' => '新しい', 'published_on' => '2026-08-01']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.news.index'))
            ->assertInertia(fn ($page) => $page
                ->where('news.data.0.title', '新しい')
                ->where('news.data.1.title', '古い')
            );
    }

    #[Test]
    public function 掲載日と種別が表示用の形式で渡される(): void
    {
        $admin = Admin::factory()->create();
        News::factory()->create([
            'published_on' => '2026-08-05',
            'category' => NewsCategory::ProductInfo,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.news.index'))
            ->assertInertia(fn ($page) => $page
                ->where('news.data.0.published_on', '2026.08.05')
                ->where('news.data.0.published_on_input', '2026-08-05')
                ->where('news.data.0.category', '商品情報')
                ->has('news.data.0.category_tone')
            );
    }

    #[Test]
    public function 種別の選択肢が渡される(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.news.index'))
            ->assertInertia(fn ($page) => $page
                ->where('categoryOptions', ['新商品', 'お知らせ', '商品情報'])
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.news.index'))
            ->assertRedirect(route('admin.login'));
    }
}
