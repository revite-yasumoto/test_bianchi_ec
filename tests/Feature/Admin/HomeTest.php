<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_view_home(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/Home'));
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.home'))
            ->assertRedirect(route('admin.login'));
    }
}
