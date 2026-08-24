<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Auth;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Mail\Front\RegistrationCompleted;
use App\Models\Prefecture;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private Prefecture $prefecture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prefecture = Prefecture::factory()->create(['name' => '東京都']);
    }

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
            'tel' => '090-0000-0000',
            'postal_code' => '150-0041',
            'prefecture_id' => $this->prefecture->id,
            'city' => '渋谷区',
            'address_line1' => '架空町1-2-3',
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
    public function 会員登録画面に都道府県の選択肢が渡る(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Auth/Register')
                ->where('prefectures.0.name', '東京都')
            );
    }

    #[Test]
    public function 会員登録すると既定の配送先が作られる(): void
    {
        $this->post(route('register.store'), $this->validPayload());

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'label' => '自宅',
            'recipient_name' => '山田 太郎',
            'postal_code' => '150-0041',
            'prefecture_id' => $this->prefecture->id,
            'city' => '渋谷区',
            'address_line1' => '架空町1-2-3',
            'tel' => '090-0000-0000',
            'is_default' => true,
        ]);
    }

    #[Test]
    public function 電話番号が未入力なら登録されない(): void
    {
        $this->post(route('register.store'), $this->validPayload(['tel' => '']))
            ->assertSessionHasErrors('tel');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 住所が未入力なら登録されない(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'postal_code' => '',
            'city' => '',
            'address_line1' => '',
        ]))->assertSessionHasErrors(['postal_code', 'city', 'address_line1']);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 住所の検証に失敗すると会員も配送先も作られない(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'prefecture_id' => 999999,
        ]))->assertSessionHasErrors('prefecture_id');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('user_addresses', 0);
    }

    #[Test]
    public function 生年月日と性別が保存される(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'birth_year' => '1990',
            'birth_month' => '4',
            'birth_day' => '1',
            'gender' => Gender::Female->value,
        ]));

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertSame('1990-04-01', $user->birthday?->toDateString());
        $this->assertSame(Gender::Female, $user->gender);
    }

    #[Test]
    public function 生年月日と性別は未選択でも登録できる(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'birth_year' => '',
            'birth_month' => '',
            'birth_day' => '',
            'gender' => '',
        ]))->assertRedirect(route('register.complete'));

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertNull($user->birthday);
        $this->assertNull($user->gender);
    }

    #[Test]
    public function 生年月日を一部だけ選ぶと登録されない(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'birth_year' => '1990',
            'birth_month' => '4',
        ]))->assertSessionHasErrors('birth_year');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 実在しない生年月日は登録されない(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'birth_year' => '1990',
            'birth_month' => '2',
            'birth_day' => '30',
        ]))->assertSessionHasErrors('birth_year');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function 未来の生年月日は登録されない(): void
    {
        $tomorrow = CarbonImmutable::tomorrow();

        $this->post(route('register.store'), $this->validPayload([
            'birth_year' => $tomorrow->format('Y'),
            'birth_month' => $tomorrow->format('n'),
            'birth_day' => $tomorrow->format('j'),
        ]))->assertSessionHasErrors('birth_year');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function メールマガジンのチェックを外すと購読しない状態で保存される(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'is_newsletter_subscribed' => false,
        ]));

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertFalse($user->is_newsletter_subscribed);
    }

    #[Test]
    public function メールマガジンを受け取る状態で登録できる(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'is_newsletter_subscribed' => true,
        ]));

        $user = User::query()->where('email', 'taro@example.test')->sole();

        $this->assertTrue($user->is_newsletter_subscribed);
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
    public function 同一の送信元からの連続した会員登録は制限される(): void
    {
        foreach (range(1, 5) as $index) {
            $this->post(route('register.store'), $this->validPayload([
                'email' => "taro{$index}@example.test",
            ]))->assertSessionHasNoErrors();
        }

        $this->post(route('register.store'), $this->validPayload([
            'email' => 'taro6@example.test',
        ]))->assertTooManyRequests();

        $this->assertDatabaseCount('users', 5);
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
