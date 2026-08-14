<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田 太郎',
            'name_kana' => 'ヤマダ タロウ',
            'email' => 'taro@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agree' => true,
        ], $overrides);
    }

    #[Test]
    public function 会員登録画面が表示される(): void
    {
        $this->get(route('register'))
            ->assertInertia(fn ($page) => $page->component('front/Auth/Register'));
    }

    #[Test]
    public function 会員登録するとユーザーが作成されログイン状態になる(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('top'));
        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.test',
            'name' => '山田 太郎',
            'status' => UserStatus::Active->value,
        ]);
        $this->assertAuthenticatedAs(User::query()->where('email', 'taro@example.test')->sole());
    }

    #[Test]
    public function 会員が1件も無いとき会員番号は開始番号で採番される(): void
    {
        $this->post(route('register.store'), $this->validPayload());

        $this->assertDatabaseHas('users', ['member_code' => 'M-100001']);
    }

    #[Test]
    public function 会員番号は既存の最大連番の次の番号で採番される(): void
    {
        User::factory()->create(['member_code' => 'M-100238']);

        $this->post(route('register.store'), $this->validPayload());

        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.test',
            'member_code' => 'M-100239',
        ]);
    }

    #[Test]
    public function 登録済みのメールアドレスでは登録できない(): void
    {
        User::factory()->create(['email' => 'taro@example.test']);

        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function パスワードが8文字未満では登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password' => 'short12',
            'password_confirmation' => 'short12',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    #[Test]
    public function パスワードと確認用パスワードが一致しないと登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password_confirmation' => 'different123',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    #[Test]
    public function 利用規約に同意しないと登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'agree' => false,
        ]));

        $response->assertSessionHasErrors('agree');
        $this->assertGuest();
    }

    #[Test]
    public function 氏名カナが全角カタカナ以外では登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'name_kana' => 'やまだ たろう',
        ]));

        $response->assertSessionHasErrors('name_kana');
        $this->assertGuest();
    }

    #[Test]
    public function 氏名カナは未入力でも登録できる(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'name_kana' => '',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'taro@example.test']);
    }

    #[Test]
    public function ログイン済みの会員は会員登録画面を開けない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('top'));
    }
}
