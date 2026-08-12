<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ログイン済みの管理者はダッシュボードを表示できる(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Dashboard/Index')
                ->has('summary')
                ->has('chart', 7)
                ->has('latestOrders')
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function ログイン成功後はダッシュボードへ遷移する(): void
    {
        $admin = Admin::factory()->create();

        $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
