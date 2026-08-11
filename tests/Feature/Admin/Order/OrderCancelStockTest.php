<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderCancelStockTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    private function makeStock(int $quantity): Stock
    {
        $variant = ProductVariant::factory()->create();

        return Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function キャンセルへの更新で明細の数量分だけ在庫が戻る(): void
    {
        $stock = $this->makeStock(5);
        $order = Order::factory()->create(['status' => OrderStatus::Received]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $stock->product_variant_id,
            'quantity' => 3,
        ]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => OrderStatus::Cancelled->value],
        );

        $this->assertSame(8, $stock->refresh()->quantity);
    }

    #[Test]
    public function 複数明細でもそれぞれの在庫が戻る(): void
    {
        $first = $this->makeStock(1);
        $second = $this->makeStock(10);
        $order = Order::factory()->create(['status' => OrderStatus::Received]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $first->product_variant_id,
            'quantity' => 2,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $second->product_variant_id,
            'quantity' => 4,
        ]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => OrderStatus::Cancelled->value],
        );

        $this->assertSame(3, $first->refresh()->quantity);
        $this->assertSame(14, $second->refresh()->quantity);
    }

    #[Test]
    public function 商品が削除済みの明細は在庫を戻さずスキップされる(): void
    {
        $stock = $this->makeStock(5);
        $order = Order::factory()->create(['status' => OrderStatus::Received]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => null,
            'quantity' => 3,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $stock->product_variant_id,
            'quantity' => 2,
        ]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => OrderStatus::Cancelled->value],
        );

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(7, $stock->refresh()->quantity);
    }

    #[Test]
    public function キャンセル以外の遷移では在庫が変わらない(): void
    {
        $stock = $this->makeStock(5);
        $order = Order::factory()->create(['status' => OrderStatus::Received]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $stock->product_variant_id,
            'quantity' => 3,
        ]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => OrderStatus::Preparing->value],
        );

        $this->assertSame(5, $stock->refresh()->quantity);
    }

    #[Test]
    public function 遷移が拒否された場合は在庫もステータスも変わらない(): void
    {
        $stock = $this->makeStock(5);
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $stock->product_variant_id,
            'quantity' => 3,
        ]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.orders.status.update', $order),
            ['status' => OrderStatus::Cancelled->value],
        );

        $this->assertSame(OrderStatus::Shipped, $order->refresh()->status);
        $this->assertSame(5, $stock->refresh()->quantity);
        $this->assertDatabaseCount('order_status_histories', 0);
    }
}
