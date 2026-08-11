<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ログアウトするとトップページへ戻り未認証になる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('top'));
        $this->assertGuest();
    }

    #[Test]
    public function ログアウト時にセッションが無効化される(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession([]);
        $originalSessionId = $this->app['session']->getId();

        $this->post(route('logout'));

        $this->assertNotSame($originalSessionId, $this->app['session']->getId());
    }
}
