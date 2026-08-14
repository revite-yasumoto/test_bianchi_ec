<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Contact;

use App\Mail\Admin\ContactReceived;
use App\Mail\Front\ContactAcknowledgement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'product_name' => '架空ジャージ 2026',
            'body' => 'サイズ展開について教えてください。',
            ...$overrides,
        ];
    }

    #[Test]
    public function 未ログインでもお問い合わせを送信できる(): void
    {
        $this->post(route('contact.store'), $this->payload())
            ->assertSessionHas('success', '送信しました。3営業日以内にご返信いたします。');

        $this->assertDatabaseHas('contacts', [
            'user_id' => null,
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'product_name' => '架空ジャージ 2026',
            'body' => 'サイズ展開について教えてください。',
        ]);
    }

    #[Test]
    public function ログイン中は会員が記録される(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contact.store'), $this->payload());

        $this->assertDatabaseHas('contacts', ['user_id' => $user->id]);
    }

    #[Test]
    public function 対象商品は省略できる(): void
    {
        $this->post(route('contact.store'), $this->payload(['product_name' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contacts', ['product_name' => null]);
    }

    #[Test]
    public function 必須項目が未入力なら送信されない(): void
    {
        $this->post(route('contact.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'body']);

        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function メールアドレスの形式が誤っていれば送信されない(): void
    {
        $this->post(route('contact.store'), $this->payload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function 本文が九文字なら送信されない(): void
    {
        $this->post(route('contact.store'), $this->payload(['body' => str_repeat('あ', 9)]))
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function 本文が十文字なら送信できる(): void
    {
        $this->post(route('contact.store'), $this->payload(['body' => str_repeat('あ', 10)]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
    }

    #[Test]
    public function 本文が上限を超えると送信されない(): void
    {
        $this->post(route('contact.store'), $this->payload(['body' => str_repeat('あ', 5001)]))
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function 同一の送信元からの連投は制限される(): void
    {
        foreach (range(1, 10) as $ignored) {
            $this->post(route('contact.store'), $this->payload())
                ->assertSessionHasNoErrors();
        }

        $this->post(route('contact.store'), $this->payload())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('contacts', 10);
    }

    #[Test]
    public function 送信すると管理者への通知と送信者への控えが送られる(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->payload());

        // 送信者宛に管理者が混ざらないこと・管理者宛に送信者が混ざらないことも併せて確認する
        Mail::assertSent(
            ContactReceived::class,
            fn (ContactReceived $mail): bool => $mail->hasTo('admin@example.test')
                && $mail->hasTo('uketsuke@example.test')
                && ! $mail->hasTo('taro@example.test'),
        );
        Mail::assertSent(
            ContactAcknowledgement::class,
            fn (ContactAcknowledgement $mail): bool => $mail->hasTo('taro@example.test')
                && count($mail->to) === 1,
        );
    }

    #[Test]
    public function 管理者の宛先が未設定でも送信者への控えは送られる(): void
    {
        Mail::fake();
        config(['mail.admin_addresses' => []]);

        $this->post(route('contact.store'), $this->payload())
            ->assertSessionHas('success');

        Mail::assertNotSent(ContactReceived::class);
        Mail::assertSent(ContactAcknowledgement::class);
    }
}
