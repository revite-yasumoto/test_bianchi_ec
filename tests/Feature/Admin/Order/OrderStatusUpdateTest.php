<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Mail\Front\OrderShipped;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function putStatus(Order $order, OrderStatus $to, array $extra = []): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => $to->value, ...$extra],
        );
    }

    #[Test]
    public function 許可された遷移でステータスを更新できる(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $response = $this->putStatus($order, OrderStatus::PaymentConfirmed);

        $response->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(OrderStatus::PaymentConfirmed, $order->refresh()->status);
    }

    #[Test]
    public function 更新時に変更履歴が1行記録される(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $this->putStatus($order, OrderStatus::Preparing);

        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'admin_id' => $this->admin->id,
            'from_status' => OrderStatus::Received->value,
            'to_status' => OrderStatus::Preparing->value,
        ]);
    }

    #[Test]
    public function キャンセルへの更新でキャンセル日時が記録される(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Received,
            'cancelled_at' => null,
        ]);

        $this->putStatus($order, OrderStatus::Cancelled);

        $this->assertNotNull($order->refresh()->cancelled_at);
    }

    #[Test]
    public function 出荷済みからは変更できない(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);

        $response = $this->putStatus($order, OrderStatus::Cancelled);

        $response->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Shipped, $order->refresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    #[Test]
    public function キャンセルからは変更できない(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);

        $response = $this->putStatus($order, OrderStatus::Received);

        $response->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    #[Test]
    public function 許可されていない遷移は拒否される(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::AwaitingPayment]);

        // 入金待ちから出荷準備中へは直接遷移できない
        $response = $this->putStatus($order, OrderStatus::Preparing);

        $response->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::AwaitingPayment, $order->refresh()->status);
    }

    #[Test]
    public function 同じステータスへの更新は拒否される(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $response = $this->putStatus($order, OrderStatus::Received);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    #[Test]
    public function 存在しないステータス値は拒否される(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => 'unknown_status'],
        );

        $response->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Received, $order->refresh()->status);
    }

    #[Test]
    public function 未認証はステータスを更新できない(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $this->put(route('admin.orders.status.update', $order), [
            'status' => OrderStatus::Preparing->value,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(OrderStatus::Received, $order->refresh()->status);
    }

    #[Test]
    public function 出荷済みへの変更で送り状番号が保存される(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::Preparing]);

        $this->putStatus($order, OrderStatus::Shipped, [
            'tracking_number' => '1234-5678-9012',
        ])->assertSessionHasNoErrors();

        $this->assertSame('1234-5678-9012', $order->refresh()->tracking_number);
    }

    #[Test]
    public function 送り状番号は未入力でも出荷済みにできる(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::Preparing]);

        $this->putStatus($order, OrderStatus::Shipped)->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertNull($order->tracking_number);
    }

    #[Test]
    public function 出荷済み以外への変更では送り状番号が設定されない(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $this->putStatus($order, OrderStatus::PaymentConfirmed, [
            'tracking_number' => '1234-5678-9012',
        ])->assertSessionHasNoErrors();

        $this->assertNull($order->refresh()->tracking_number);
    }

    #[Test]
    public function 送信を選ぶと会員へ出荷完了メールが送られる(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'status' => OrderStatus::Preparing,
            'customer_email' => 'taro@example.test',
        ]);

        $this->putStatus($order, OrderStatus::Shipped, [
            'tracking_number' => '1234-5678-9012',
            'notifies_customer' => true,
        ]);

        Mail::assertSent(
            OrderShipped::class,
            fn (OrderShipped $mail): bool => $mail->hasTo('taro@example.test'),
        );
    }

    #[Test]
    public function 送信を選ばなければメールは送られない(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::Preparing]);

        $this->putStatus($order, OrderStatus::Shipped, ['notifies_customer' => false]);

        Mail::assertNothingSent();
    }

    #[Test]
    public function 出荷済み以外への変更ではメールが送られない(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::Received]);

        $this->putStatus($order, OrderStatus::PaymentConfirmed, [
            'notifies_customer' => true,
        ]);

        Mail::assertNothingSent();
    }
}
