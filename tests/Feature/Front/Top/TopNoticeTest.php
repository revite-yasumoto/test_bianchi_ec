<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Top;

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TopNoticeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 掲載中のお知らせのうち最新の一件が返る(): void
    {
        Notice::factory()->create([
            'title' => '古いお知らせ',
            'display_start_on' => now()->subDays(5)->toDateString(),
            'display_end_on' => now()->addDays(5)->toDateString(),
        ]);
        Notice::factory()->create([
            'title' => '新しいお知らせ',
            'display_start_on' => now()->subDay()->toDateString(),
            'display_end_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('notice.title', '新しいお知らせ'));
    }

    #[Test]
    public function 掲載開始前のお知らせは返らない(): void
    {
        Notice::factory()->create([
            'title' => '掲載予定',
            'display_start_on' => now()->addDay()->toDateString(),
            'display_end_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('notice', null));
    }

    #[Test]
    public function 掲載終了後のお知らせは返らない(): void
    {
        Notice::factory()->create([
            'title' => '掲載終了',
            'display_start_on' => now()->subDays(10)->toDateString(),
            'display_end_on' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('notice', null));
    }

    #[Test]
    public function 掲載中のお知らせが無ければ空になる(): void
    {
        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('notice', null));
    }
}
