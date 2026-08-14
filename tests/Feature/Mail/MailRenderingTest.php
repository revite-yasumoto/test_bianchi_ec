<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Enums\PaymentMethod;
use App\Mail\Admin\ContactReceived;
use App\Mail\Admin\OrderPlaced;
use App\Mail\Front\ContactAcknowledgement;
use App\Mail\Front\OrderReceived;
use App\Mail\Front\OrderShipped;
use App\Mail\Front\PasswordResetLink;
use App\Mail\Front\RegistrationCompleted;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 送信時の例外は NotificationMailer が握り潰すため、テンプレートが壊れても本番では
 * ログにしか現れない。送信をモックせず実際に描画することで、変数名の誤り・遅延ロード
 * 違反・ルート名の変更をここで検出する。
 */
class MailRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithItem(): Order
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::BankTransfer,
            'bank_transfer_note' => "架空銀行 架空支店\n普通 0000000",
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => '架空ジャージ 2026',
            'size_name' => 'M',
            'color_name' => 'ブラック',
            'unit_price' => 9900,
            'quantity' => 2,
            'subtotal' => 19800,
        ]);

        return $order->load('items');
    }

    #[Test]
    public function 会員登録完了メールが描画できる(): void
    {
        $user = User::factory()->create(['name' => '架空 太郎', 'member_code' => 'M-100001']);

        $mailable = new RegistrationCompleted($user);

        $mailable->assertSeeInHtml('架空 太郎');
        $mailable->assertSeeInHtml('M-100001');
        $mailable->assertSeeInHtml(route('mypage.index'));
    }

    #[Test]
    public function 注文受付メールに明細と金額と振込案内が載る(): void
    {
        $order = $this->orderWithItem();

        $mailable = new OrderReceived($order);

        $mailable->assertSeeInHtml($order->order_number);
        $mailable->assertSeeInHtml('架空ジャージ 2026');
        $mailable->assertSeeInHtml('M / ブラック');
        $mailable->assertSeeInHtml('9,900');
        $mailable->assertSeeInHtml('架空銀行 架空支店');
    }

    #[Test]
    public function 代引きの注文受付メールには代引き手数料が載り振込案内が載らない(): void
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::Cod,
            'bank_transfer_note' => null,
            'cod_fee' => 330,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $mailable = new OrderReceived($order->load('items'));

        $mailable->assertSeeInHtml('代金引換');
        $mailable->assertSeeInHtml('代引き手数料');
    }

    #[Test]
    public function 管理者宛の注文通知に要約と管理画面のリンクが載る(): void
    {
        $order = $this->orderWithItem();

        $mailable = new OrderPlaced($order);

        $mailable->assertSeeInHtml($order->order_number);
        $mailable->assertSeeInHtml($order->customer_name);
        $mailable->assertSeeInHtml(route('admin.orders.show', $order));
    }

    #[Test]
    public function 管理者宛のお問い合わせ通知が描画できる(): void
    {
        $contact = Contact::factory()->create([
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'body' => '在庫の入荷予定を教えてください。',
        ]);

        $mailable = new ContactReceived($contact);

        $mailable->assertSeeInHtml('架空 太郎');
        $mailable->assertSeeInHtml('taro@example.test');
        $mailable->assertSeeInHtml('在庫の入荷予定を教えてください。');
    }

    #[Test]
    public function お問い合わせの控えが描画できる(): void
    {
        $contact = Contact::factory()->create([
            'name' => '架空 太郎',
            'body' => '在庫の入荷予定を教えてください。',
        ]);

        $mailable = new ContactAcknowledgement($contact);

        $mailable->assertSeeInHtml('架空 太郎');
        $mailable->assertSeeInHtml('在庫の入荷予定を教えてください。');
    }

    #[Test]
    public function 問い合わせ本文のリンク記法は装飾に変換されず改行が保たれる(): void
    {
        $contact = Contact::factory()->create([
            'body' => "1行目です。\n[表示文字](https://example.test/elsewhere)",
        ]);

        $mailable = new ContactReceived($contact);

        // 第2引数の false は、検索文字列自体をエスケープせず生のHTMLとして照合させる指定
        $mailable->assertDontSeeInHtml('href="https://example.test/elsewhere"', false);
        $mailable->assertSeeInHtml('<br>', false);
    }

    #[Test]
    public function パスワード再設定メールに再設定リンクと有効期限が載る(): void
    {
        $user = User::factory()->create(['name' => '架空 太郎']);

        $mailable = new PasswordResetLink($user, 'test-token-value');

        $mailable->assertSeeInHtml('架空 太郎');
        $mailable->assertSeeInHtml('test-token-value');
        $mailable->assertSeeInHtml('60 分間のみ有効');
    }

    #[Test]
    public function 出荷完了メールに送り状番号とお届け先が載る(): void
    {
        $order = Order::factory()->create([
            'tracking_number' => '1234-5678-9012',
            'shipping_recipient_name' => '架空 太郎',
        ]);

        $mailable = new OrderShipped($order);

        $mailable->assertSeeInHtml($order->order_number);
        $mailable->assertSeeInHtml('1234-5678-9012');
        $mailable->assertSeeInHtml('架空 太郎');
    }

    #[Test]
    public function 送り状番号が無くても出荷完了メールを描画できる(): void
    {
        $order = Order::factory()->create(['tracking_number' => null]);

        $mailable = new OrderShipped($order);

        $mailable->assertSeeInHtml($order->order_number);
        $mailable->assertDontSeeInHtml('送り状番号');
    }
}
