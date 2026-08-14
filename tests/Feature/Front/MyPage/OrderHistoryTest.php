<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $productNames
     */
    private function createOrder(array $attributes = [], array $productNames = ['架空ジャージ']): Order
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, ...$attributes]);

        foreach ($productNames as $name) {
            OrderItem::factory()->create(['order_id' => $order->id, 'product_name' => $name]);
        }

        return $order;
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('mypage.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 自分の注文だけが表示される(): void
    {
        $this->createOrder(['order_number' => 'BNC-0001-0001']);

        $other = User::factory()->create();
        $othersOrder = Order::factory()->create(['user_id' => $other->id]);
        OrderItem::factory()->create(['order_id' => $othersOrder->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/MyPage/Orders')
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'BNC-0001-0001')
            );
    }

    #[Test]
    public function 注文日の新しい順に並ぶ(): void
    {
        $this->createOrder(['order_number' => 'BNC-0001-0001', 'ordered_at' => now()->subDays(3)]);
        $this->createOrder(['order_number' => 'BNC-0002-0002', 'ordered_at' => now()]);

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.data.0.order_number', 'BNC-0002-0002')
                ->where('orders.data.1.order_number', 'BNC-0001-0001')
            );
    }

    #[Test]
    public function 明細が一点なら商品名だけが要約に出る(): void
    {
        $this->createOrder([], ['架空ジャージ']);

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertInertia(fn ($page) => $page->where('orders.data.0.items_summary', '架空ジャージ'));
    }

    #[Test]
    public function 明細が複数なら先頭の商品名に残りの点数が添えられる(): void
    {
        $this->createOrder([], ['架空ジャージ', '架空ヘルメット', '架空グローブ']);

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertInertia(fn ($page) => $page->where('orders.data.0.items_summary', '架空ジャージ ほか2点'));
    }

    #[Test]
    public function 注文受付と入金待ちの注文はキャンセルできる状態で返る(): void
    {
        $this->createOrder(['status' => OrderStatus::Received, 'ordered_at' => now()]);
        $this->createOrder(['status' => OrderStatus::AwaitingPayment, 'ordered_at' => now()->subDay()]);

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.data.0.is_cancelable', true)
                ->where('orders.data.1.is_cancelable', true)
            );
    }

    #[Test]
    public function 入金確認済み以降の注文はキャンセルできない状態で返る(): void
    {
        $this->createOrder(['status' => OrderStatus::PaymentConfirmed, 'ordered_at' => now()]);
        $this->createOrder(['status' => OrderStatus::Shipped, 'ordered_at' => now()->subDay()]);
        $this->createOrder(['status' => OrderStatus::Cancelled, 'ordered_at' => now()->subDays(2)]);

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.data.0.is_cancelable', false)
                ->where('orders.data.1.is_cancelable', false)
                ->where('orders.data.2.is_cancelable', false)
            );
    }

    #[Test]
    public function 一ページあたり十件までで区切られる(): void
    {
        foreach (range(1, 11) as $index) {
            $this->createOrder(['ordered_at' => now()->subDays($index)]);
        }

        $this->actingAs($this->user)
            ->get(route('mypage.index'))
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 10)
                ->where('orders.total', 11)
            );
    }
}
