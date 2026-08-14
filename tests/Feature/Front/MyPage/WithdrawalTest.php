<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password';

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
    private function payload(array $overrides = []): array
    {
        return [
            'password' => self::PASSWORD,
            'agree' => true,
            ...$overrides,
        ];
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('mypage.withdrawal'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 退会画面が表示される(): void
    {
        $this->actingAs($this->user)
            ->get(route('mypage.withdrawal'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('front/MyPage/Withdrawal'));
    }

    #[Test]
    public function 退会するとステータスが退会になりログアウトする(): void
    {
        $this->actingAs($this->user)
            ->post(route('mypage.withdrawal.store'), $this->payload())
            ->assertRedirect(route('top'))
            ->assertSessionHas('success');

        $this->assertSame(UserStatus::Withdrawn, $this->user->refresh()->status);
        $this->assertGuest();
    }

    #[Test]
    public function パスワードが誤っていれば退会されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('mypage.withdrawal.store'), $this->payload(['password' => 'wrong-password']))
            ->assertSessionHasErrors('password');

        $this->assertSame(UserStatus::Active, $this->user->refresh()->status);
        $this->assertAuthenticatedAs($this->user);
    }

    #[Test]
    public function 同意していなければ退会されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('mypage.withdrawal.store'), $this->payload(['agree' => false]))
            ->assertSessionHasErrors('agree');

        $this->assertSame(UserStatus::Active, $this->user->refresh()->status);
    }

    #[Test]
    public function 退会した会員はログインできない(): void
    {
        $this->actingAs($this->user)->post(route('mypage.withdrawal.store'), $this->payload());

        $this->post(route('login.store'), [
            'email' => 'taro@example.test',
            'password' => self::PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function 退会すると記憶トークンが作り直される(): void
    {
        $this->user->forceFill(['remember_token' => 'previous-token'])->save();

        $this->actingAs($this->user)->post(route('mypage.withdrawal.store'), $this->payload());

        $this->assertNotSame('previous-token', $this->user->refresh()->remember_token);
    }

    #[Test]
    public function 退会しても注文は残る(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->post(route('mypage.withdrawal.store'), $this->payload());

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function 退会したメールアドレスでは再登録できない(): void
    {
        $this->actingAs($this->user)->post(route('mypage.withdrawal.store'), $this->payload());

        $this->post(route('register.store'), [
            'name' => '架空 次郎',
            'name_kana' => 'カクウ ジロウ',
            'email' => 'taro@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agree' => true,
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }
}
