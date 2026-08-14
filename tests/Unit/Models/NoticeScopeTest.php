<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NoticeState;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function 掲載期間内のお知らせだけが取得される(): void
    {
        $displaying = Notice::factory()->create([
            'display_start_on' => '2026-08-10',
            'display_end_on' => '2026-08-20',
        ]);
        Notice::factory()->create([
            'display_start_on' => '2026-08-13',
            'display_end_on' => '2026-08-25',
        ]);
        Notice::factory()->create([
            'display_start_on' => '2026-07-01',
            'display_end_on' => '2026-08-11',
        ]);

        $displayable = Notice::query()->displayable()->get();

        $this->assertCount(1, $displayable);
        $this->assertSame($displaying->id, $displayable->first()?->id);
    }

    #[Test]
    public function 掲載開始日と掲載終了日の当日は掲載期間に含まれる(): void
    {
        Notice::factory()->create([
            'display_start_on' => '2026-08-12',
            'display_end_on' => '2026-08-12',
        ]);

        $this->assertSame(1, Notice::query()->displayable()->count());
    }

    #[Test]
    public function 掲載状態が当日日付から算出される(): void
    {
        $notice = Notice::factory()->make([
            'display_start_on' => '2026-08-13',
            'display_end_on' => '2026-08-20',
        ]);

        $this->assertSame(NoticeState::Scheduled, $notice->state());
    }
}
