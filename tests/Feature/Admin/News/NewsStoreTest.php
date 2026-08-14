<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\News;

use App\Models\Admin;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsStoreTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'published_on' => '2026-08-12',
            'category' => '新商品',
            'title' => '新モデルの取り扱いを開始しました',
            'body' => "本文の1行目\n本文の2行目",
            'is_published' => true,
            ...$overrides,
        ];
    }

    #[Test]
    public function ニュースを作成できる(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.news.store'), $this->payload());

        $response->assertSessionHasNoErrors();

        $news = News::query()->firstOrFail();
        $this->assertSame('新モデルの取り扱いを開始しました', $news->title);
        $this->assertSame('2026-08-12', $news->published_on->toDateString());
        $this->assertSame('新商品', $news->category->value);
        $this->assertTrue($news->is_published);
    }

    #[Test]
    public function 非公開として作成できる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.news.store'), $this->payload(['is_published' => false]))
            ->assertSessionHasNoErrors();

        $this->assertFalse(News::query()->firstOrFail()->is_published);
    }

    #[Test]
    public function タイトルが未入力では作成できない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.news.store'), $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('news', 0);
    }

    #[Test]
    public function 許可されていない種別は弾かれる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.news.store'), $this->payload(['category' => 'キャンペーン']))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('news', 0);
    }

    #[Test]
    public function 掲載日の形式が不正だと作成できない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.news.store'), $this->payload(['published_on' => '2026/08/12']))
            ->assertSessionHasErrors('published_on');
    }

    #[Test]
    public function 本文が上限を超えると作成できない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.news.store'), $this->payload(['body' => str_repeat('あ', 10001)]))
            ->assertSessionHasErrors('body');
    }

    #[Test]
    public function 未認証は作成できない(): void
    {
        $this->post(route('admin.news.store'), $this->payload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('news', 0);
    }
}
