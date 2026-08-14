<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Auth;

use App\Mail\Front\PasswordResetLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'new-password-2026';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'taro@example.test']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resetPayload(string $token, array $overrides = []): array
    {
        return [
            'token' => $token,
            'email' => 'taro@example.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
            ...$overrides,
        ];
    }

    #[Test]
    public function 未ログインでパスワード再設定の申請画面を開ける(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('front/Auth/ForgotPassword'));
    }

    #[Test]
    public function ログイン中は申請画面を開けない(): void
    {
        $this->actingAs($this->user)
            ->get(route('password.request'))
            ->assertRedirect(route('top'));
    }

    #[Test]
    public function 登録済みのアドレスに再設定メールが送られる(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => 'taro@example.test'])
            ->assertSessionHas('success');

        Mail::assertSent(
            PasswordResetLink::class,
            fn (PasswordResetLink $mail): bool => $mail->hasTo('taro@example.test'),
        );
    }

    #[Test]
    public function 未登録のアドレスでもメールは送られず同じ応答になる(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Mail::assertNothingSent();
    }

    #[Test]
    public function トークン付きのリンクから再設定画面を開ける(): void
    {
        $token = Password::createToken($this->user);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'taro@example.test']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Auth/ResetPassword')
                ->where('token', $token)
                ->where('email', 'taro@example.test')
            );
    }

    #[Test]
    public function 再設定したパスワードでログインできる(): void
    {
        $token = Password::createToken($this->user);

        $this->post(route('password.update'), $this->resetPayload($token))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $this->user->refresh()->password));

        $this->post(route('login.store'), [
            'email' => 'taro@example.test',
            'password' => self::NEW_PASSWORD,
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->user);
    }

    #[Test]
    public function パスワードを再設定してもログイン状態にはならない(): void
    {
        $token = Password::createToken($this->user);

        $this->post(route('password.update'), $this->resetPayload($token));

        $this->assertGuest();
    }

    #[Test]
    public function 無効なトークンではパスワードを変更できない(): void
    {
        $original = $this->user->password;

        $this->post(route('password.update'), $this->resetPayload('invalid-token'))
            ->assertSessionHasErrors('email');

        $this->assertSame($original, $this->user->refresh()->password);
    }

    #[Test]
    public function 期限切れのトークンではパスワードを変更できない(): void
    {
        $token = Password::createToken($this->user);
        $original = $this->user->password;

        // 有効期限は config/auth.php の passwords.users.expire で60分
        $this->travel(61)->minutes();

        $this->post(route('password.update'), $this->resetPayload($token))
            ->assertSessionHasErrors('email');

        $this->assertSame($original, $this->user->refresh()->password);
    }

    #[Test]
    public function 八文字未満のパスワードには変更できない(): void
    {
        $token = Password::createToken($this->user);

        $this->post(route('password.update'), $this->resetPayload($token, [
            'password' => 'short12',
            'password_confirmation' => 'short12',
        ]))->assertSessionHasErrors('password');

        $this->assertFalse(Hash::check('short12', $this->user->refresh()->password));
    }

    #[Test]
    public function 確認用パスワードが一致しなければ変更されない(): void
    {
        $token = Password::createToken($this->user);
        $original = $this->user->password;

        $this->post(route('password.update'), $this->resetPayload($token, [
            'password_confirmation' => 'mismatched-2026',
        ]))->assertSessionHasErrors('password');

        $this->assertSame($original, $this->user->refresh()->password);
    }
}
