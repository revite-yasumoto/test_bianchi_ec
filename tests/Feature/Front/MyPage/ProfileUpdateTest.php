<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Enums\Gender;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
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
    public function 生年月日と性別を更新できる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload([
                'birth_year' => '1990',
                'birth_month' => '4',
                'birth_day' => '1',
                'gender' => Gender::Male->value,
            ]))
            ->assertSessionHas('success', '会員情報を更新しました');

        $this->user->refresh();

        $this->assertSame('1990-04-01', $this->user->birthday?->toDateString());
        $this->assertSame(Gender::Male, $this->user->gender);
    }

    #[Test]
    public function メールマガジンのチェックを外すと購読しない状態になる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload([
                'is_newsletter_subscribed' => false,
            ]));

        $this->user->refresh();

        $this->assertFalse($this->user->is_newsletter_subscribed);
    }

    #[Test]
    public function 電話番号が未入力なら更新されない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['tel' => '']))
            ->assertSessionHasErrors('tel');

        $this->assertSame('090-0000-0000', $this->user->fresh()?->tel);
    }

    #[Test]
    public function 生年月日を一部だけ選ぶと更新されない(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload([
                'birth_year' => '1990',
                'birth_day' => '1',
            ]))
            ->assertSessionHasErrors('birth_year');

        $this->assertNull($this->user->fresh()?->birthday);
    }

    #[Test]
    public function 未来の生年月日は更新されない(): void
    {
        $tomorrow = CarbonImmutable::tomorrow();

        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload([
                'birth_year' => $tomorrow->format('Y'),
                'birth_month' => $tomorrow->format('n'),
                'birth_day' => $tomorrow->format('j'),
            ]))
            ->assertSessionHasErrors('birth_year');

        $this->assertNull($this->user->fresh()?->birthday);
    }

    #[Test]
    public function 現在の生年月日と性別が初期値として表示される(): void
    {
        $this->user->update([
            'birthday' => '1990-04-01',
            'gender' => Gender::Female,
            'is_newsletter_subscribed' => false,
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.profile'))
            ->assertInertia(fn ($page) => $page
                ->component('front/MyPage/Profile')
                ->where('profile.birth_year', '1990')
                ->where('profile.birth_month', '4')
                ->where('profile.birth_day', '1')
                ->where('profile.gender', Gender::Female->value)
                ->where('profile.is_newsletter_subscribed', false)
            );
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
    public function カナは省略できる(): void
    {
        $this->actingAs($this->user)
            ->put(route('mypage.profile.update'), $this->payload(['name_kana' => null]))
            ->assertSessionHasNoErrors();

        $this->assertNull($this->user->refresh()->name_kana);
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
