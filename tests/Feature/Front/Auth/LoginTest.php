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
}
