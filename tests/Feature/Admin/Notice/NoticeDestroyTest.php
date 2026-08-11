<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Notice;

use App\Models\Admin;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeDestroyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function お知らせを削除できる(): void
    {
        $admin = Admin::factory()->create();
        $notice = Notice::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.notices.destroy', [$notice->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notices', 0);
    }

    #[Test]
    public function 未認証は削除できない(): void
    {
        $notice = Notice::factory()->create();

        $this->delete(route('admin.notices.destroy', [$notice->id]))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('notices', 1);
    }
}
