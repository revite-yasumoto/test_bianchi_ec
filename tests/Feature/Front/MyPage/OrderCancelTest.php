<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

class OrderCancelTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function createOrder(OrderStatus $status): Order
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => $status,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order;
    }

    #[Test]
    public function 注文受付の注文をキャンセルできる(): void
    {
        $order = $this->createOrder(OrderStatus::Received);

        $this->actingAs($this->user)
            ->post(route('mypage.orders.cancel', [$order]))
            ->assertRedirect(route('mypage.orders.show', [$order]))
            ->assertSessionHas('success', '注文をキャンセルしました');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    #[Test]
    public function 入金待ちの注文をキャンセルできる(): void
    {
        $order = $this->createOrder(OrderStatus::AwaitingPayment);

        $this->actingAs($this->user)->post(route('mypage.orders.cancel', [$order]));

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    #[Test]
    public function 入金確認済みの注文はキャンセルできない(): void
    {
        $order = $this->createOrder(OrderStatus::PaymentConfirmed);

        $this->actingAs($this->user)
            ->post(route('mypage.orders.cancel', [$order]))
            ->assertSessionHas('error', 'この注文はキャンセルできません');

        $this->assertSame(OrderStatus::PaymentConfirmed, $order->refresh()->status);
    }

    #[Test]
    public function 出荷済みの注文はキャンセルできない(): void
    {
        $order = $this->createOrder(OrderStatus::Shipped);

        $this->actingAs($this->user)->post(route('mypage.orders.cancel', [$order]));

        $this->assertSame(OrderStatus::Shipped, $order->refresh()->status);
    }

    #[Test]
    public function キャンセル済みの注文は再度キャンセルできない(): void
    {
        $order = $this->createOrder(OrderStatus::Cancelled);

        $this->actingAs($this->user)
            ->post(route('mypage.orders.cancel', [$order]))
            ->assertSessionHas('error', 'この注文はキャンセルできません');

        $this->assertDatabaseCount('order_status_histories', 0);
    }

    #[Test]
    public function 他人の注文はキャンセルできない(): void
    {
        $other = User::factory()->create();
        $othersOrder = Order::factory()->create([
            'user_id' => $other->id,
            'status' => OrderStatus::Received,
        ]);

        $this->actingAs($this->user)
            ->post(route('mypage.orders.cancel', [$othersOrder]))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Received, $othersOrder->refresh()->status);
    }

    #[Test]
    public function キャンセルすると在庫が戻る(): void
    {
        $variant = $this->createVariantWithStock(stock: 5);
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::Received,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($this->user)->post(route('mypage.orders.cancel', [$order]));

        $this->assertSame(7, Stock::query()->where('product_variant_id', $variant->id)->value('quantity'));
    }

    #[Test]
    public function 会員によるキャンセルの履歴は管理者を持たずに記録される(): void
    {
        $order = $this->createOrder(OrderStatus::Received);

        $this->actingAs($this->user)->post(route('mypage.orders.cancel', [$order]));

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'admin_id' => null,
            'from_status' => OrderStatus::Received->value,
            'to_status' => OrderStatus::Cancelled->value,
        ]);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $order = $this->createOrder(OrderStatus::Received);

        $this->post(route('mypage.orders.cancel', [$order]))->assertRedirect(route('login'));
    }
}
