<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Notice;

use App\Models\Admin;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function お知らせを更新できる(): void
    {
        $admin = Admin::factory()->create();
        $notice = Notice::factory()->create([
            'title' => '変更前のタイトル',
            'display_start_on' => '2026-08-01',
            'display_end_on' => '2026-08-05',
        ]);

        $response = $this->actingAs($admin, 'admin')->put(
            route('admin.notices.update', [$notice->id]),
            [
                'title' => '変更後のタイトル',
                'body' => '更新した本文',
                'display_start_on' => '2026-09-01',
                'display_end_on' => '2026-09-30',
            ],
        );

        $response->assertSessionHasNoErrors();

        $notice->refresh();
        $this->assertSame('変更後のタイトル', $notice->title);
        $this->assertSame('2026-09-01', $notice->display_start_on->toDateString());
        $this->assertSame('2026-09-30', $notice->display_end_on->toDateString());
    }

    #[Test]
    public function 掲載終了日が掲載開始日より前だと更新されない(): void
    {
        $admin = Admin::factory()->create();
        $notice = Notice::factory()->create(['title' => '変更前のタイトル']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.notices.update', [$notice->id]), [
                'title' => '変更後のタイトル',
                'body' => '更新した本文',
                'display_start_on' => '2026-09-10',
                'display_end_on' => '2026-09-09',
            ])
            ->assertSessionHasErrors('display_end_on');

        $this->assertSame('変更前のタイトル', $notice->refresh()->title);
    }

    #[Test]
    public function 未認証は更新できない(): void
    {
        $notice = Notice::factory()->create(['title' => '変更前のタイトル']);

        $this->put(route('admin.notices.update', [$notice->id]), [
            'title' => '変更後のタイトル',
            'body' => '更新した本文',
            'display_start_on' => '2026-09-01',
            'display_end_on' => '2026-09-30',
        ])->assertRedirect(route('admin.login'));

        $this->assertSame('変更前のタイトル', $notice->refresh()->title);
    }
}
