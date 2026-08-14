<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AggregateProductRankingsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-01 01:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function コマンドを実行するとランキングが作られる(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Shipped,
            'ordered_at' => '2026-07-10 10:00:00',
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $this->artisan('rankings:aggregate')
            ->expectsOutputToContain('2026-07')
            ->assertSuccessful();

        $this->assertDatabaseHas('product_rankings', [
            'target_year_month' => '2026-07',
            'product_id' => $product->id,
            'rank_position' => 1,
        ]);
    }
}
