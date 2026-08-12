<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_page_renders(): void
    {
        $this->get(route('admin.login'))
            ->assertInertia(fn ($page) => $page->component('admin/Auth/Login'));
    }

    #[Test]
    public function successful_login_redirects_to_a_working_page(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page->component('admin/Dashboard/Index'));
    }

    #[Test]
    public function admin_can_login_with_admin_code(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    #[Test]
    public function admin_can_login_with_email(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'login_id' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest('admin');
    }

    #[Test]
    public function login_fails_for_unregistered_account(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login_id' => 'nobody@bianchi.demo',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest('admin');
    }

    #[Test]
    public function login_requires_login_id(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login_id' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login_id');
    }

    #[Test]
    public function login_requires_password_of_at_least_eight_characters(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest('admin');
    }

    #[Test]
    public function login_is_rate_limited_after_five_failed_attempts(): void
    {
        $admin = Admin::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), [
                'login_id' => $admin->admin_code,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest('admin');
        $this->assertNotSame($admin->id, auth('admin')->id());
    }

    #[Test]
    public function successful_login_regenerates_session_id(): void
    {
        $admin = Admin::factory()->create();

        $this->withSession([]);
        $originalSessionId = $this->app['session']->getId();

        $this->post(route('admin.login.store'), [
            'login_id' => $admin->admin_code,
            'password' => 'password',
        ]);

        $this->assertNotSame($originalSessionId, $this->app['session']->getId());
    }
}
