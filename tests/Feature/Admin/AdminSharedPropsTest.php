<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminSharedPropsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function shared_props_expose_admin_name_and_email_only(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.home'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Home')
                ->where('auth.admin.name', $admin->name)
                ->where('auth.admin.email', $admin->email)
                ->missing('auth.admin.id')
                ->missing('auth.admin.admin_code')
                ->missing('auth.admin.password')
                ->missing('auth.admin.remember_token')
            );
    }

    /**
     * 単位02（会員認証）が未実装で front 側のページが存在しないため、
     * このテスト内だけに front 相当のページを一時定義して検証する。
     */
    #[Test]
    public function shared_props_do_not_expose_admin_key_outside_admin_paths(): void
    {
        Route::middleware('web')
            ->get('/__test/front-page', fn () => Inertia::render('__test/Blank'))
            ->name('__test.front-page');
        Route::getRoutes()->refreshNameLookups();

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('__test.front-page'))
            ->assertInertia(fn ($page) => $page->missing('auth'));
    }
}
