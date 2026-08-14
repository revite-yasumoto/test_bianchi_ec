<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        Carbon::setTestNow('2026-08-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeOrder(string $orderedAt, int $total, OrderStatus $status = OrderStatus::Received): Order
    {
        return Order::factory()->create([
            'ordered_at' => $orderedAt,
            'total' => $total,
            'status' => $status,
        ]);
    }

    #[Test]
    public function 本日の売上はキャンセル注文を除いて合計される(): void
    {
        $this->makeOrder('2026-08-12 09:00:00', 30000);
        $this->makeOrder('2026-08-12 09:30:00', 20000, OrderStatus::Shipped);
        $this->makeOrder('2026-08-12 09:45:00', 90000, OrderStatus::Cancelled);
        $this->makeOrder('2026-08-11 09:00:00', 70000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.today_sales', 50000)
            );
    }

    #[Test]
    public function 今月の売上は当月の注文のみを集計する(): void
    {
        $this->makeOrder('2026-08-01 00:00:00', 10000);
        $this->makeOrder('2026-08-12 09:00:00', 25000);
        $this->makeOrder('2026-07-31 23:59:59', 80000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.month_sales', 35000)
                ->where('summary.month_sales_note', '8月1日〜12日')
            );
    }

    #[Test]
    public function 前日に売上があるときは前日比が表示される(): void
    {
        $this->makeOrder('2026-08-11 09:00:00', 10000);
        $this->makeOrder('2026-08-12 09:00:00', 15000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.today_sales_note', '前日比 +50%')
            );
    }

    #[Test]
    public function 前日の売上が0のときは前日実績なしと表示される(): void
    {
        $this->makeOrder('2026-08-12 09:00:00', 15000);
        $this->makeOrder('2026-08-11 09:00:00', 90000, OrderStatus::Cancelled);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.today_sales_note', '前日実績なし')
            );
    }

    #[Test]
    public function 新規注文の件数はキャンセルを含む本日受付分になる(): void
    {
        $this->makeOrder('2026-08-12 09:00:00', 10000);
        $this->makeOrder('2026-08-12 20:00:00', 20000, OrderStatus::Cancelled);
        $this->makeOrder('2026-08-11 09:00:00', 30000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.new_order_count', 2)
            );
    }

    #[Test]
    public function 入金待ちの件数は日付によらず集計される(): void
    {
        $this->makeOrder('2026-08-12 09:00:00', 10000, OrderStatus::AwaitingPayment);
        $this->makeOrder('2026-07-01 09:00:00', 20000, OrderStatus::AwaitingPayment);
        $this->makeOrder('2026-08-12 10:00:00', 30000, OrderStatus::PaymentConfirmed);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.awaiting_payment_count', 2)
            );
    }

    #[Test]
    public function 注文がないときはすべて0になる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.today_sales', 0)
                ->where('summary.month_sales', 0)
                ->where('summary.new_order_count', 0)
                ->where('summary.awaiting_payment_count', 0)
                ->where('summary.today_sales_note', '前日実績なし')
            );
    }
}
