<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => '架空 太郎',
            'name_kana' => 'カクウ タロウ',
            'email' => 'taro@example.test',
            'tel' => '090-0000-0000',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => '架空 次郎',
            'name_kana' => 'カクウ ジロウ',
            'email' => 'jiro@example.test',
            'tel' => '080-0000-0000',
            ...$overrides,
        ];
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('mypage.profile'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 現在の会員情報が初期値として表示される(): void
    {
        $this->actingAs($this->user)
            ->get(route('mypage.profile'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/MyPage/Profile')
                ->where('profile.name', '架空 太郎')
                ->where('profile.email', 'taro@example.test')
                ->where('profile.tel', '090-0000-0000')
            );
    }

    #[Test]
    public function 会員情報を更新できる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload())
            ->assertSessionHas('success', '会員情報を更新しました');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => '架空 次郎',
            'name_kana' => 'カクウ ジロウ',
            'email' => 'jiro@example.test',
            'tel' => '080-0000-0000',
        ]);
    }

    #[Test]
    public function 現在のメールアドレスのまま更新できる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['email' => 'taro@example.test']))
            ->assertSessionHasNoErrors();

        $this->assertSame('架空 次郎', $this->user->refresh()->name);
    }

    #[Test]
    public function 他の会員が使用中のメールアドレスは登録できない(): void
    {
        User::factory()->create(['email' => 'used@example.test']);

        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['email' => 'used@example.test']))
            ->assertSessionHasErrors('email');

        $this->assertSame('taro@example.test', $this->user->refresh()->email);
    }

    #[Test]
    public function 氏名が未入力なら更新されない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertSame('架空 太郎', $this->user->refresh()->name);
    }

    #[Test]
    public function カナが全角カタカナ以外なら更新されない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['name_kana' => 'かくう じろう']))
            ->assertSessionHasErrors('name_kana');
    }

    #[Test]
    public function カナと電話番号は省略できる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['name_kana' => null, 'tel' => null]))
            ->assertSessionHasNoErrors();

        $user = $this->user->refresh();
        $this->assertNull($user->name_kana);
        $this->assertNull($user->tel);
    }

    #[Test]
    public function 会員情報を変更しても確定済みの注文の注文者名は変わらない(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => '架空 太郎',
            'customer_email' => 'taro@example.test',
        ]);

        $this->actingAs($this->user)->put(route('mypage.profile.update'), $this->payload());

        $order->refresh();
        $this->assertSame('架空 太郎', $order->customer_name);
        $this->assertSame('taro@example.test', $order->customer_email);
    }
}
