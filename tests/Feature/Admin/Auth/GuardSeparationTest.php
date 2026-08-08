<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuardSeparationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function member_session_cannot_access_admin_only_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * 単位02（会員認証）が未実装で `login` ルートが存在しないため、
     * front側の `web` ガード保護ルートをこのテスト内だけに一時定義して検証する。
     * bootstrap/app.php の redirectGuestsTo がパスで front/admin を振り分けることを確認する。
     */
    #[Test]
    public function admin_session_cannot_access_member_only_route(): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/__test/front-login', fn () => 'login-page')->name('login');
            Route::get('/__test/web-protected', fn () => 'ok')
                ->middleware('auth:web')
                ->name('__test.web-protected');
        });

        // ->name() は addLookups() 後に action['as'] を設定するため、name lookup の再構築が必要
        Route::getRoutes()->refreshNameLookups();

        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('__test.web-protected'));

        $response->assertRedirect(route('login'));
    }
}
