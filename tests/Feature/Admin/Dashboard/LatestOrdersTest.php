<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LatestOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    #[Test]
    public function 最新の注文が注文日の降順で5件返る(): void
    {
        foreach (range(1, 7) as $day) {
            Order::factory()->create([
                'order_number' => sprintf('BNC-2608-%04d', $day),
                'ordered_at' => sprintf('2026-08-%02d 10:00:00', $day),
            ]);
        }

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('latestOrders', 5)
                ->where('latestOrders.0.order_number', 'BNC-2608-0007')
                ->where('latestOrders.4.order_number', 'BNC-2608-0003')
            );
    }

    #[Test]
    public function 注文が5件未満のときは件数分だけ返る(): void
    {
        Order::factory()->count(2)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page->has('latestOrders', 2));
    }

    #[Test]
    public function 氏名と合計金額とステータスが渡される(): void
    {
        Order::factory()->create([
            'customer_name' => '山田 太郎',
            'total' => 24800,
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('latestOrders.0.customer_name', '山田 太郎')
                ->where('latestOrders.0.total', 24800)
                ->where('latestOrders.0.status', 'awaiting_payment')
                ->where('latestOrders.0.status_label', '入金待ち')
                ->has('latestOrders.0.status_tone')
            );
    }

    #[Test]
    public function キャンセル注文も最新の注文に表示される(): void
    {
        Order::factory()->create([
            'ordered_at' => '2026-08-12 10:00:00',
            'status' => OrderStatus::Cancelled,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('latestOrders', 1)
                ->where('latestOrders.0.status_label', 'キャンセル')
            );
    }
}
