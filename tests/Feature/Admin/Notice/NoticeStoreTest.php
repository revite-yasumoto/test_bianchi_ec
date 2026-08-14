<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Notice;

use App\Models\Admin;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeStoreTest extends TestCase
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
            'title' => 'システムメンテナンスのお知らせ',
            'body' => "本文の1行目\n本文の2行目",
            'display_start_on' => '2026-08-10',
            'display_end_on' => '2026-08-20',
            ...$overrides,
        ];
    }

    #[Test]
    public function お知らせを作成できる(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notices.store'), $this->payload());

        $response->assertSessionHasNoErrors();

        $notice = Notice::query()->firstOrFail();
        $this->assertSame('システムメンテナンスのお知らせ', $notice->title);
        $this->assertSame('2026-08-10', $notice->display_start_on->toDateString());
        $this->assertSame('2026-08-20', $notice->display_end_on->toDateString());
    }

    #[Test]
    public function 掲載開始日と掲載終了日が同日でも作成できる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notices.store'), $this->payload([
                'display_start_on' => '2026-08-10',
                'display_end_on' => '2026-08-10',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notices', 1);
    }

    #[Test]
    public function 掲載終了日が掲載開始日より前だと作成できない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notices.store'), $this->payload([
                'display_start_on' => '2026-08-10',
                'display_end_on' => '2026-08-09',
            ]))
            ->assertSessionHasErrors('display_end_on');

        $this->assertDatabaseCount('notices', 0);
    }

    #[Test]
    public function タイトルが未入力では作成できない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notices.store'), $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('notices', 0);
    }

    #[Test]
    public function 掲載開始日の形式が不正だと作成できない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notices.store'), $this->payload(['display_start_on' => '2026/08/10']))
            ->assertSessionHasErrors('display_start_on');
    }

    #[Test]
    public function 未認証は作成できない(): void
    {
        $this->post(route('admin.notices.store'), $this->payload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('notices', 0);
    }
}
