<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Notice;

use App\Models\Admin;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeIndexTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        Carbon::setTestNow('2026-08-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function 掲載期間から掲載中と予約と掲載終了が算出される(): void
    {
        Notice::factory()->create([
            'title' => '掲載中のお知らせ',
            'display_start_on' => '2026-08-10',
            'display_end_on' => '2026-08-20',
        ]);
        Notice::factory()->create([
            'title' => '予約中のお知らせ',
            'display_start_on' => '2026-08-15',
            'display_end_on' => '2026-08-25',
        ]);
        Notice::factory()->create([
            'title' => '終了したお知らせ',
            'display_start_on' => '2026-07-01',
            'display_end_on' => '2026-07-31',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.notices.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Notice/Index')
                ->has('notices.data', 3)
                ->where('notices.data.0.title', '予約中のお知らせ')
                ->where('notices.data.0.state', 'scheduled')
                ->where('notices.data.0.state_label', '予約')
                ->where('notices.data.1.title', '掲載中のお知らせ')
                ->where('notices.data.1.state', 'displaying')
                ->where('notices.data.1.state_label', '掲載中')
                ->where('notices.data.2.title', '終了したお知らせ')
                ->where('notices.data.2.state', 'ended')
                ->where('notices.data.2.state_label', '掲載終了')
            );
    }

    #[Test]
    public function 掲載開始日の当日は掲載中になる(): void
    {
        Notice::factory()->create([
            'display_start_on' => '2026-08-12',
            'display_end_on' => '2026-08-20',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.notices.index'))
            ->assertInertia(fn ($page) => $page
                ->where('notices.data.0.state', 'displaying')
            );
    }

    #[Test]
    public function 掲載終了日の当日は掲載中になる(): void
    {
        Notice::factory()->create([
            'display_start_on' => '2026-08-01',
            'display_end_on' => '2026-08-12',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.notices.index'))
            ->assertInertia(fn ($page) => $page
                ->where('notices.data.0.state', 'displaying')
            );
    }

    #[Test]
    public function 掲載期間がラベル形式で渡される(): void
    {
        Notice::factory()->create([
            'display_start_on' => '2026-08-05',
            'display_end_on' => '2026-08-20',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.notices.index'))
            ->assertInertia(fn ($page) => $page
                ->where('notices.data.0.period_label', '2026.08.05 - 2026.08.20')
                ->where('notices.data.0.display_start_on', '2026-08-05')
                ->where('notices.data.0.display_end_on', '2026-08-20')
                ->has('notices.data.0.state_tone')
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.notices.index'))
            ->assertRedirect(route('admin.login'));
    }
}
