<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Mail\Admin\OrderPlaced;
use App\Mail\Front\OrderReceived;
use App\Models\EcSetting;
use App\Models\Order;
use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    private Prefecture $tokyo;

    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-13 10:00:00');

        $this->user = User::factory()->create();
        EcSetting::factory()->create();
        $this->tokyo = $this->createPrefectureWithShipping('東京都', 500, 3);
        $this->address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function placeOrder(PaymentMethod $paymentMethod = PaymentMethod::BankTransfer): TestResponse
    {
        return $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, $paymentMethod))
            ->post(route('orders.store'));
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->post(route('orders.store'))->assertRedirect(route('login'));
    }

    #[Test]
    public function セッションを持たずに注文を確定しようとすると購入手続きへ戻される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->post(route('orders.store'))
            ->assertRedirect(route('checkout.index'));
    }

    #[Test]
    public function カートが空のときは注文が作成されない(): void
    {
        $this->placeOrder()
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'カートに商品がありません。');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function 注文が作成され注文完了画面へリダイレクトされる(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock(3000), 2);

        $response = $this->placeOrder();

        $order = Order::query()->sole();
        $response->assertRedirect(route('orders.complete', [$order]));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $this->user->id,
            'subtotal' => 6000,
            'shipping_fee' => 500,
            'cod_fee' => 0,
            'total' => 6500,
        ]);
    }

    #[Test]
    public function 注文番号は接頭辞と年月と月内連番で採番される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder();

        $this->assertSame('BNC-2608-0001', Order::query()->sole()->order_number);
    }

    #[Test]
    public function 月内の連番は既存の注文の件数に続く(): void
    {
        Order::factory()->count(2)->create(['ordered_at' => Carbon::parse('2026-08-01 09:00:00')]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder();

        $this->assertDatabaseHas('orders', ['order_number' => 'BNC-2608-0003']);
    }

    #[Test]
    public function 前月までの注文は月内連番に含まれない(): void
    {
        Order::factory()->count(3)->create(['ordered_at' => Carbon::parse('2026-07-31 23:59:59')]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder();

        $this->assertDatabaseHas('orders', ['order_number' => 'BNC-2608-0001']);
    }

    #[Test]
    public function 銀行振込の注文は入金待ちで作成される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder(PaymentMethod::BankTransfer);

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
        $this->assertNotNull($order->bank_transfer_note);
    }

    #[Test]
    public function 代引きの注文は注文受付で作成され振込案内文を持たない(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder(PaymentMethod::Cod);

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Received, $order->status);
        $this->assertNull($order->bank_transfer_note);
        $this->assertSame(330, $order->cod_fee);
    }

    #[Test]
    public function 注文確定後にカートが空になる(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder();

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function 他の会員のカートは削除されない(): void
    {
        $other = User::factory()->create();
        $this->addToCart($other, $this->createVariantWithStock());
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder();

        $this->assertDatabaseHas('cart_items', ['user_id' => $other->id]);
    }

    #[Test]
    public function ステータス履歴の初期行が作成される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder(PaymentMethod::Cod);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => Order::query()->sole()->id,
            'admin_id' => null,
            'from_status' => null,
            'to_status' => OrderStatus::Received->value,
        ]);
    }

    #[Test]
    public function 注文確定後は購入手続きのセッションが消える(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder()
            ->assertSessionMissing('checkout.address_id')
            ->assertSessionMissing('checkout.payment_method');
    }

    #[Test]
    public function 選択済みの配送先が削除されていると購入手続きへ戻される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());
        $this->address->delete();

        $this->placeOrder()->assertRedirect(route('checkout.index'));

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function 注文を確定すると会員と管理者にメールが送られる(): void
    {
        Mail::fake();
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder();

        // 会員宛に管理者が混ざらないこと・管理者宛に会員が混ざらないことも併せて確認する
        Mail::assertSent(
            OrderReceived::class,
            fn (OrderReceived $mail): bool => $mail->hasTo($this->user->email) && count($mail->to) === 1,
        );
        Mail::assertSent(
            OrderPlaced::class,
            fn (OrderPlaced $mail): bool => $mail->hasTo('admin@example.test')
                && $mail->hasTo('uketsuke@example.test')
                && ! $mail->hasTo($this->user->email),
        );
    }

    #[Test]
    public function メールの送信に失敗しても注文は成立する(): void
    {
        Log::spy();
        // 会員宛・管理者宛の2通とも失敗させ、握り潰したうえで注文完了画面へ進むことを確認する
        Mail::shouldReceive('to->send')->twice()->andThrow(new RuntimeException('接続できません'));
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->placeOrder()->assertRedirect(route('orders.complete', Order::query()->sole()));

        Log::shouldHaveReceived('error')->twice();
    }

    #[Test]
    public function 注文が失敗したときはメールが送られない(): void
    {
        Mail::fake();
        $this->addToCart($this->user, $this->createVariantWithStock(stock: 0));

        $this->placeOrder();

        $this->assertDatabaseCount('orders', 0);
        Mail::assertNothingSent();
    }
}
