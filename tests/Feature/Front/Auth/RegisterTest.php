<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Auth;

use App\Enums\UserStatus;
use App\Mail\Front\RegistrationCompleted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
    public function 会員登録するとユーザーが作成され完了画面へ遷移する(): void
    {
        $this->post(route('register.store'), $this->validPayload())
            ->assertRedirect(route('register.complete'));

        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.test',
            'name' => '山田 太郎',
            'status' => UserStatus::Active->value,
        ]);
    }

    #[Test]
    public function 会員登録してもログイン状態にはならない(): void
    {
        $this->post(route('register.store'), $this->validPayload());

        $this->assertGuest();
    }

    #[Test]
    public function 登録直後は会員登録完了画面を開ける(): void
    {
        $this->post(route('register.store'), $this->validPayload());

        $this->get(route('register.complete'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('front/Auth/RegisterComplete'));
    }

    #[Test]
    public function 登録を経ずに会員登録完了画面を開くとログイン画面へ戻される(): void
    {
        $this->get(route('register.complete'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 会員登録完了画面は再読み込みすると開けない(): void
    {
        $this->post(route('register.store'), $this->validPayload());
        $this->get(route('register.complete'))->assertOk();

        $this->get(route('register.complete'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 購入手続きから登録するとログイン後に購入手続きへ戻る(): void
    {
        // 未ログインで要認証ページへ入ろうとした時点で、戻り先がセッションに記録される
        $this->get(route('checkout.index'))->assertRedirect(route('login'));

        // 完了画面への遷移で戻り先を消費していないことを、ログインより前に直接確かめる
        $this->post(route('register.store'), $this->validPayload())
            ->assertRedirect(route('register.complete'))
            ->assertSessionHas('url.intended', route('checkout.index'));

        // ログイン失敗時も直前のGETと同じ URL へ戻るため、成功したことを別途確定させる
        $this->post(route('login.store'), [
            'email' => 'taro@example.test',
            'password' => 'password123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('checkout.index'));

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
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function パスワードが8文字未満では登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password' => 'short12',
            'password_confirmation' => 'short12',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'taro@example.test']);
    }

    #[Test]
    public function パスワードと確認用パスワードが一致しないと登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password_confirmation' => 'different123',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'taro@example.test']);
    }

    #[Test]
    public function 利用規約に同意しないと登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'agree' => false,
        ]));

        $response->assertSessionHasErrors('agree');
        $this->assertDatabaseMissing('users', ['email' => 'taro@example.test']);
    }

    #[Test]
    public function 氏名カナが全角カタカナ以外では登録できない(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'name_kana' => 'やまだ たろう',
        ]));

        $response->assertSessionHasErrors('name_kana');
        $this->assertDatabaseMissing('users', ['email' => 'taro@example.test']);
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

    #[Test]
    public function 会員登録すると登録完了メールが送られる(): void
    {
        Mail::fake();

        $this->post(route('register.store'), $this->validPayload());

        Mail::assertSent(
            RegistrationCompleted::class,
            fn (RegistrationCompleted $mail): bool => $mail->hasTo('taro@example.test'),
        );
    }

    #[Test]
    public function 登録に失敗したときはメールが送られない(): void
    {
        Mail::fake();

        $this->post(route('register.store'), $this->validPayload(['email' => 'invalid']))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }
}
