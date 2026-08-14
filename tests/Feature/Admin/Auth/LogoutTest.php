<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_logout(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    #[Test]
    public function logout_invalidates_the_session(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->withSession(['some_flag' => 'value']);
        $originalSessionId = $this->app['session']->getId();

        $this->post(route('admin.logout'));

        $this->assertNotSame($originalSessionId, $this->app['session']->getId());
        $this->assertFalse($this->app['session']->has('some_flag'));
    }

    #[Test]
    public function guest_cannot_access_logout_route(): void
    {
        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
    }
}
