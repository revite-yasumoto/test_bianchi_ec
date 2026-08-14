<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Notice;

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeIndexTest extends TestCase
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
    public function 未ログインでも掲載中のお知らせを閲覧できる(): void
    {
        $this->createNotice(['title' => '架空の配送遅延のお知らせ']);

        $this->get(route('notices.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Notice/Index')
                ->has('notices.data', 1)
                ->where('notices.data.0.title', '架空の配送遅延のお知らせ')
            );
    }

    #[Test]
    public function 掲載開始前のお知らせは表示されない(): void
    {
        $this->createNotice([
            'title' => '掲載前のお知らせ',
            'display_start_on' => now()->addDay()->toDateString(),
            'display_end_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->get(route('notices.index'))
            ->assertInertia(fn ($page) => $page->has('notices.data', 0));
    }

    #[Test]
    public function 掲載終了後のお知らせは表示されない(): void
    {
        $this->createNotice([
            'title' => '掲載終了のお知らせ',
            'display_start_on' => now()->subDays(5)->toDateString(),
            'display_end_on' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('notices.index'))
            ->assertInertia(fn ($page) => $page->has('notices.data', 0));
    }

    #[Test]
    public function 掲載開始日の当日は表示される(): void
    {
        $this->createNotice([
            'title' => '本日開始のお知らせ',
            'display_start_on' => now()->toDateString(),
            'display_end_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->get(route('notices.index'))
            ->assertInertia(fn ($page) => $page->has('notices.data', 1));
    }

    #[Test]
    public function 掲載終了日の当日は表示される(): void
    {
        $this->createNotice([
            'title' => '本日終了のお知らせ',
            'display_start_on' => now()->subDays(3)->toDateString(),
            'display_end_on' => now()->toDateString(),
        ]);

        $this->get(route('notices.index'))
            ->assertInertia(fn ($page) => $page->has('notices.data', 1));
    }

    #[Test]
    public function 掲載期間が表示用と機械可読の双方で渡される(): void
    {
        // 掲載期間で絞り込まれないよう当日を期間内に固定する
        $this->travelTo('2026-08-10');

        $this->createNotice([
            'display_start_on' => '2026-08-05',
            'display_end_on' => '2026-08-20',
        ]);

        $this->get(route('notices.index'))
            ->assertInertia(fn ($page) => $page
                ->where('notices.data.0.period_start', '2026.08.05')
                ->where('notices.data.0.period_start_iso', '2026-08-05')
                ->where('notices.data.0.period_end', '2026.08.20')
                ->where('notices.data.0.period_end_iso', '2026-08-20')
            );
    }

    #[Test]
    public function 掲載中のお知らせが無くても閲覧できる(): void
    {
        $this->get(route('notices.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('notices.data', 0));
    }
}
