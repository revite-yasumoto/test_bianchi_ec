<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Notice;

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * お知らせの登録経路は Tests\Feature\Admin\Notice\NoticeStoreTest で検証済みのため、
 * 本テストでは Factory で直接用意する。
 */
class NoticeShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createNotice(array $attributes = []): Notice
    {
        return Notice::factory()->create([
            'display_start_on' => now()->subDay()->toDateString(),
            'display_end_on' => now()->addDay()->toDateString(),
            ...$attributes,
        ]);
    }

    #[Test]
    public function 未ログインでも掲載中のお知らせ詳細を閲覧できる(): void
    {
        $notice = $this->createNotice(['title' => '架空の配送遅延のお知らせ']);

        $this->get(route('notices.show', $notice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Notice/Show')
                ->where('notice.id', $notice->id)
                ->where('notice.title', '架空の配送遅延のお知らせ')
            );
    }

    #[Test]
    public function 本文と掲載期間が詳細に渡される(): void
    {
        $this->travelTo('2026-08-10');

        $notice = $this->createNotice([
            'body' => "1行目の本文\n2行目の本文",
            'display_start_on' => '2026-08-05',
            'display_end_on' => '2026-08-20',
        ]);

        $this->get(route('notices.show', $notice))
            ->assertInertia(fn ($page) => $page
                ->where('notice.body', "1行目の本文\n2行目の本文")
                ->where('notice.period_start', '2026.08.05')
                ->where('notice.period_start_iso', '2026-08-05')
                ->where('notice.period_end', '2026.08.20')
                ->where('notice.period_end_iso', '2026-08-20')
            );
    }

    #[Test]
    public function 掲載開始前のお知らせ詳細は見つからない(): void
    {
        $notice = $this->createNotice([
            'display_start_on' => now()->addDay()->toDateString(),
            'display_end_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->get(route('notices.show', $notice))->assertNotFound();
    }

    #[Test]
    public function 掲載終了後のお知らせ詳細は見つからない(): void
    {
        $notice = $this->createNotice([
            'display_start_on' => now()->subDays(5)->toDateString(),
            'display_end_on' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('notices.show', $notice))->assertNotFound();
    }

    #[Test]
    public function 掲載開始日の当日は詳細を閲覧できる(): void
    {
        $notice = $this->createNotice([
            'display_start_on' => now()->toDateString(),
            'display_end_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->get(route('notices.show', $notice))->assertOk();
    }

    #[Test]
    public function 掲載終了日の当日は詳細を閲覧できる(): void
    {
        $notice = $this->createNotice([
            'display_start_on' => now()->subDays(3)->toDateString(),
            'display_end_on' => now()->toDateString(),
        ]);

        $this->get(route('notices.show', $notice))->assertOk();
    }

    #[Test]
    public function 存在しないお知らせの詳細は見つからない(): void
    {
        $this->get(route('notices.show', 999999))->assertNotFound();
    }
}
