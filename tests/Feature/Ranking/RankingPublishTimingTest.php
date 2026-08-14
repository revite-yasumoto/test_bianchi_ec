<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Models\Product;
use App\Models\ProductRanking;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RankingPublishTimingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeRanking(string $yearMonth, string $productName): void
    {
        $product = Product::factory()->create(['name' => $productName]);

        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Stock::factory()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        ProductRanking::factory()->create([
            'target_year_month' => $yearMonth,
            'category_id' => null,
            'product_id' => $product->id,
            'rank_position' => 1,
            'aggregated_at' => $yearMonth.'-01 01:00:00',
        ]);
    }

    #[Test]
    public function 公開時刻の直前は前々月分が表示される(): void
    {
        $this->makeRanking('2026-06', '6月の1位');
        $this->makeRanking('2026-07', '7月の1位');

        Carbon::setTestNow('2026-08-01 06:59:59');

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('rankings.all.0.name', '6月の1位'));
    }

    #[Test]
    public function 公開時刻を過ぎると前月分に切り替わる(): void
    {
        $this->makeRanking('2026-06', '6月の1位');
        $this->makeRanking('2026-07', '7月の1位');

        Carbon::setTestNow('2026-08-01 07:00:00');

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page->where('rankings.all.0.name', '7月の1位'));
    }

    #[Test]
    public function 公開開始前の集計しかなければランキングは空になる(): void
    {
        $this->makeRanking('2026-07', '7月の1位');

        Carbon::setTestNow('2026-08-01 06:00:00');

        $this->get(route('top'))
            ->assertInertia(fn ($page) => $page
                ->where('rankings', [])
                ->where('rankingUpdatedAt', null)
            );
    }
}
