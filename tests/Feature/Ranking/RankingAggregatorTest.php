<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRanking;
use App\Services\Ranking\RankingAggregator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RankingAggregatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-08-01 の実行を想定し、集計対象月は 2026-07 になる
        Carbon::setTestNow('2026-08-01 01:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function aggregator(): RankingAggregator
    {
        return $this->app->make(RankingAggregator::class);
    }

    private function sell(Product $product, int $quantity, string $orderedAt = '2026-07-15 10:00:00', OrderStatus $status = OrderStatus::Shipped): void
    {
        $order = Order::factory()->create([
            'status' => $status,
            'ordered_at' => $orderedAt,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 前月の販売数の多い順に順位が付く(): void
    {
        $few = Product::factory()->create(['name' => '少ない商品']);
        $many = Product::factory()->create(['name' => '多い商品']);

        $this->sell($few, 2);
        $this->sell($many, 5);

        $yearMonth = $this->aggregator()->aggregate();

        $this->assertSame('2026-07', $yearMonth);
        $this->assertDatabaseHas('product_rankings', [
            'target_year_month' => '2026-07',
            'category_id' => null,
            'product_id' => $many->id,
            'rank_position' => 1,
        ]);
        $this->assertDatabaseHas('product_rankings', [
            'category_id' => null,
            'product_id' => $few->id,
            'rank_position' => 2,
        ]);
    }

    #[Test]
    public function キャンセル注文は集計から除外される(): void
    {
        $product = Product::factory()->create();

        $this->sell($product, 10, status: OrderStatus::Cancelled);

        $this->aggregator()->aggregate();

        $this->assertDatabaseCount('product_rankings', 0);
    }

    #[Test]
    public function 対象月の外の注文は集計されない(): void
    {
        $product = Product::factory()->create();

        $this->sell($product, 3, orderedAt: '2026-06-30 23:59:59');
        $this->sell($product, 4, orderedAt: '2026-08-01 00:00:00');

        $this->aggregator()->aggregate();

        $this->assertDatabaseCount('product_rankings', 0);
    }

    #[Test]
    public function 削除済み商品の明細は集計から除外される(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Shipped,
            'ordered_at' => '2026-07-15 10:00:00',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_variant_id' => null,
            'quantity' => 9,
        ]);

        $this->aggregator()->aggregate();

        $this->assertDatabaseCount('product_rankings', 0);
    }

    #[Test]
    public function 全体とカテゴリ別の両方が作られる(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->sell($product, 3);

        $this->aggregator()->aggregate();

        $this->assertDatabaseHas('product_rankings', [
            'category_id' => null,
            'product_id' => $product->id,
            'rank_position' => 1,
        ]);
        $this->assertDatabaseHas('product_rankings', [
            'category_id' => $category->id,
            'product_id' => $product->id,
            'rank_position' => 1,
        ]);
    }

    #[Test]
    public function 再実行しても行が重複しない(): void
    {
        $product = Product::factory()->create();
        $this->sell($product, 3);

        $this->aggregator()->aggregate();
        $this->aggregator()->aggregate();

        $this->assertSame(
            2,
            ProductRanking::query()->where('target_year_month', '2026-07')->count(),
        );
    }

    #[Test]
    public function 保存されるのは上位十件までとする(): void
    {
        foreach (range(1, 12) as $index) {
            $this->sell(Product::factory()->create(), $index);
        }

        $this->aggregator()->aggregate();

        $this->assertSame(
            10,
            ProductRanking::query()->whereNull('category_id')->count(),
        );
    }

    #[Test]
    public function 基準日から集計対象月が決まる(): void
    {
        $product = Product::factory()->create();
        $this->sell($product, 3, orderedAt: '2026-05-10 10:00:00');

        $yearMonth = $this->aggregator()->aggregate(CarbonImmutable::parse('2026-06-01 01:00:00'));

        $this->assertSame('2026-05', $yearMonth);
        $this->assertDatabaseHas('product_rankings', [
            'target_year_month' => '2026-05',
            'product_id' => $product->id,
        ]);
    }
}
