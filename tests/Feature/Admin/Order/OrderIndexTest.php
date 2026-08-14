<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    private function makeOrder(string $number, string $customerName, OrderStatus $status, string $orderedAt): Order
    {
        return Order::factory()->create([
            'order_number' => $number,
            'customer_name' => $customerName,
            'status' => $status,
            'ordered_at' => $orderedAt,
        ]);
    }

    #[Test]
    public function 注文一覧が表示される(): void
    {
        $this->makeOrder('BNC-2607-0918', '山田 太郎', OrderStatus::Received, '2026-07-24 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Order/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'BNC-2607-0918')
                ->where('orders.data.0.customer_name', '山田 太郎')
                ->where('orders.data.0.ordered_at', '2026.07.24')
                ->where('orders.data.0.status_label', '注文受付')
                ->where('totalCount', 1)
            );
    }

    #[Test]
    public function 支払方法の表示名が渡される(): void
    {
        Order::factory()->create(['payment_method' => PaymentMethod::Cod]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.data.0.payment_method_label', '代金引換')
            );
    }

    #[Test]
    public function ステータスで絞り込める(): void
    {
        $this->makeOrder('BNC-0001-0001', '山田 太郎', OrderStatus::Received, '2026-07-24 10:00:00');
        $this->makeOrder('BNC-0001-0002', '佐藤 花子', OrderStatus::Shipped, '2026-07-25 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index', ['status' => OrderStatus::Shipped->value]))
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'BNC-0001-0002')
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function 注文番号で絞り込める(): void
    {
        $this->makeOrder('BNC-2607-0918', '山田 太郎', OrderStatus::Received, '2026-07-24 10:00:00');
        $this->makeOrder('BNC-2606-0402', '佐藤 花子', OrderStatus::Received, '2026-06-15 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index', ['q' => '2607']))
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'BNC-2607-0918')
            );
    }

    #[Test]
    public function 顧客名で絞り込める(): void
    {
        $this->makeOrder('BNC-0001-0001', '山田 太郎', OrderStatus::Received, '2026-07-24 10:00:00');
        $this->makeOrder('BNC-0001-0002', '佐藤 花子', OrderStatus::Received, '2026-06-15 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index', ['q' => '佐藤']))
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.customer_name', '佐藤 花子')
            );
    }

    #[Test]
    public function ステータスと検索語を組み合わせて絞り込める(): void
    {
        $this->makeOrder('BNC-0001-0001', '山田 太郎', OrderStatus::Received, '2026-07-24 10:00:00');
        $this->makeOrder('BNC-0001-0002', '山田 花子', OrderStatus::Shipped, '2026-07-25 10:00:00');
        $this->makeOrder('BNC-0001-0003', '佐藤 次郎', OrderStatus::Shipped, '2026-07-26 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index', [
                'status' => OrderStatus::Shipped->value,
                'q' => '山田',
            ]))
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'BNC-0001-0002')
                ->where('totalCount', 3)
            );
    }

    #[Test]
    public function 一覧は注文日の降順で並ぶ(): void
    {
        $this->makeOrder('BNC-0001-0001', '山田 太郎', OrderStatus::Received, '2026-06-15 10:00:00');
        $this->makeOrder('BNC-0001-0002', '佐藤 花子', OrderStatus::Received, '2026-07-25 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.data.0.order_number', 'BNC-0001-0002')
                ->where('orders.data.1.order_number', 'BNC-0001-0001')
            );
    }

    #[Test]
    public function 該当がない場合は空の一覧になる(): void
    {
        $this->makeOrder('BNC-0001-0001', '山田 太郎', OrderStatus::Received, '2026-07-24 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index', ['q' => '存在しない注文']))
            ->assertInertia(fn ($page) => $page->has('orders.data', 0));
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.orders.index'))
            ->assertRedirect(route('admin.login'));
    }
}
