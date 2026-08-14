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

class SalesChartTest extends TestCase
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
    public function 直近7日間が古い順に並ぶ(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('chart', 7)
                ->where('chart.0.label', '8/6')
                ->where('chart.6.label', '8/12')
            );
    }

    #[Test]
    public function 注文がない日は0で埋められる(): void
    {
        $this->makeOrder('2026-08-12 09:00:00', 12000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('chart.0.amount', 0)
                ->where('chart.5.amount', 0)
                ->where('chart.6.amount', 12000)
            );
    }

    #[Test]
    public function 同じ日の複数注文は合算されキャンセルは除外される(): void
    {
        $this->makeOrder('2026-08-10 09:00:00', 5000);
        $this->makeOrder('2026-08-10 20:00:00', 7000);
        $this->makeOrder('2026-08-10 21:00:00', 90000, OrderStatus::Cancelled);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('chart.4.label', '8/10')
                ->where('chart.4.amount', 12000)
            );
    }

    #[Test]
    public function 月をまたぐ期間でも日ごとに集計される(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        $this->makeOrder('2026-08-31 09:00:00', 8000);
        $this->makeOrder('2026-09-01 09:00:00', 3000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('chart.0.label', '8/27')
                ->where('chart.4.label', '8/31')
                ->where('chart.4.amount', 8000)
                ->where('chart.5.label', '9/1')
                ->where('chart.5.amount', 3000)
                ->where('chart.6.label', '9/2')
            );
    }

    #[Test]
    public function 期間外の注文は含まれない(): void
    {
        $this->makeOrder('2026-08-05 23:59:59', 50000);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('chart.0.amount', 0)
            );
    }
}
