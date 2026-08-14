<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Product;

use App\Models\BrowsingHistory;
use App\Models\EcSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrowsingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        EcSetting::factory()->create();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeProduct(): Product
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create(['product_id' => $product->id]);

        return $product;
    }

    #[Test]
    public function ログイン中に詳細を開くと閲覧履歴が記録される(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->user)
            ->get(route('products.show', $product))
            ->assertOk();

        $this->assertDatabaseHas('browsing_histories', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function 同じ商品を再訪しても行が増えず閲覧日時が更新される(): void
    {
        $product = $this->makeProduct();

        Carbon::setTestNow('2026-08-12 10:00:00');
        $this->actingAs($this->user)->get(route('products.show', $product));

        Carbon::setTestNow('2026-08-12 11:00:00');
        $this->actingAs($this->user)->get(route('products.show', $product));

        $this->assertDatabaseCount('browsing_histories', 1);

        $history = BrowsingHistory::query()->firstOrFail();
        $this->assertTrue($history->viewed_at->equalTo(Carbon::parse('2026-08-12 11:00:00')));
    }

    #[Test]
    public function 直近六件を超えた履歴は削除される(): void
    {
        $products = [];

        for ($index = 0; $index < 7; $index++) {
            $products[] = $this->makeProduct();
        }

        foreach ($products as $index => $product) {
            Carbon::setTestNow(Carbon::parse('2026-08-12 10:00:00')->addHours($index));
            $this->actingAs($this->user)->get(route('products.show', $product));
        }

        $this->assertDatabaseCount('browsing_histories', 6);
        $this->assertDatabaseMissing('browsing_histories', [
            'user_id' => $this->user->id,
            'product_id' => $products[0]->id,
        ]);
    }

    #[Test]
    public function 未ログインでは閲覧履歴を記録しない(): void
    {
        $product = $this->makeProduct();

        $this->get(route('products.show', $product))->assertOk();

        $this->assertDatabaseCount('browsing_histories', 0);
    }
}
