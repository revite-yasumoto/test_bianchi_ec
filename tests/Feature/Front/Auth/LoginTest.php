<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ログイン画面が表示される(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page->component('front/Auth/Login'));
    }

    #[Test]
    public function 正しいメールアドレスとパスワードでログインできる(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('top'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function パスワードが誤っている場合はログインできない(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function 未登録のメールアドレスではログインできない(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'nobody@example.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function 休会中の会員はログインできない(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function ログイン失敗が5回続くとレート制限がかかる(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function ログイン成功時にセッション識別子が再生成される(): void
    {
        $user = User::factory()->create();

        $this->withSession([]);
        $originalSessionId = $this->app['session']->getId();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotSame($originalSessionId, $this->app['session']->getId());
    }

    #[Test]
    public function ログイン後は認証前にアクセスしようとしたページへ戻る(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['url.intended' => url('/products')])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(url('/products'));
    }

    #[Test]
    public function 未ログインで要認証ルートへアクセスするとログイン画面へ送られる(): void
    {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function ログイン済みの会員はログイン画面を開けない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('top'));
    }

    private function attemptWithWrongPassword(User $user, int $times): void
    {
        foreach (range(1, $times) as $ignored) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }
    }

    #[Test]
    public function パスワードを三回間違えるとアカウントがロックされる(): void
    {
        $user = User::factory()->create();

        $this->attemptWithWrongPassword($user, 3);

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
        $this->assertSame(0, $user->failed_login_attempts);
    }

    #[Test]
    public function 二回の失敗ではロックされない(): void
    {
        $user = User::factory()->create();

        $this->attemptWithWrongPassword($user, 2);

        $user->refresh();
        $this->assertNull($user->locked_until);
        $this->assertSame(2, $user->failed_login_attempts);
    }

    #[Test]
    public function ロック中は正しいパスワードでもログインできない(): void
    {
        $user = User::factory()->create(['locked_until' => now()->addHour()]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function ロックは一時間後に解除される(): void
    {
        $user = User::factory()->create(['locked_until' => now()->addHour()]);

        $this->travel(61)->minutes();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function ログインに成功すると失敗回数がリセットされる(): void
    {
        $user = User::factory()->create(['failed_login_attempts' => 2]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    #[Test]
    public function 休会中の会員が正しいパスワードで試すと失敗回数が戻る(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
            'failed_login_attempts' => 2,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    #[Test]
    public function 存在しないアドレスへの連続した失敗ではロックの記録が残らない(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->post(route('login.store'), [
                'email' => 'unknown@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->assertDatabaseCount('users', 0);
    }
}
