<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_PASSWORD = 'current-password';

    private const NEW_PASSWORD = 'new-password-2026';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => self::CURRENT_PASSWORD]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'current_password' => self::CURRENT_PASSWORD,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
            ...$overrides,
        ];
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('mypage.password'))->assertRedirect(route('login'));
    }

    #[Test]
    public function パスワードを変更できる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.password.update'), $this->payload())
            ->assertSessionHas('success', 'パスワードを変更しました');

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $this->user->refresh()->password));
    }

    #[Test]
    public function 現在のパスワードが誤っていれば変更されない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.password.update'), $this->payload(['current_password' => 'wrong-password']))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check(self::CURRENT_PASSWORD, $this->user->refresh()->password));
    }

    #[Test]
    public function 現在と同じパスワードには変更できない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.password.update'), $this->payload([
                'password' => self::CURRENT_PASSWORD,
                'password_confirmation' => self::CURRENT_PASSWORD,
            ]))
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function 確認用パスワードが一致しなければ変更されない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.password.update'), $this->payload(['password_confirmation' => 'mismatched']))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::CURRENT_PASSWORD, $this->user->refresh()->password));
    }

    #[Test]
    public function 八文字未満のパスワードには変更できない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.password.update'), $this->payload([
                'password' => 'short12',
                'password_confirmation' => 'short12',
            ]))
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function 変更後は新しいパスワードでログインできる(): void
    {
        $this->actingAs($this->user)->put(route('mypage.password.update'), $this->payload());

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => $this->user->email,
            'password' => self::NEW_PASSWORD,
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->user);
    }

    #[Test]
    public function 変更後もログイン状態が維持される(): void
    {
        $this->actingAs($this->user)->put(route('mypage.password.update'), $this->payload());

        $this->assertAuthenticatedAs($this->user);

        $this->get(route('mypage.password'))->assertOk();
    }
}
