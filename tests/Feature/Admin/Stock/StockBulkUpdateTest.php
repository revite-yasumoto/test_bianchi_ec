<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Stock;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function makeStock(string $productName, int $quantity, ?Category $category = null): Stock
    {
        $product = Product::factory()->create([
            'name' => $productName,
            'category_id' => ($category ?? $this->category)->id,
            'has_sku' => false,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size_name' => null,
            'color_name' => null,
        ]);

        return Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function 表示中の全行を一括更新できる(): void
    {
        $first = $this->makeStock('アクセサリー', 1);
        $second = $this->makeStock('ロードスター', 2);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.stocks.bulk-update'),
            [
                'stocks' => [
                    ['id' => $first->id, 'quantity' => 10],
                    ['id' => $second->id, 'quantity' => 20],
                ],
                'page' => 1,
            ],
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(10, $first->refresh()->quantity);
        $this->assertSame(20, $second->refresh()->quantity);
    }

    #[Test]
    public function 絞り込み条件付きでも表示中の行を一括更新できる(): void
    {
        $target = $this->makeStock('チームジャージ', 1);
        $other = $this->makeStock('ロードスター', 2);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.stocks.bulk-update'),
            [
                'stocks' => [['id' => $target->id, 'quantity' => 10]],
                'q' => 'ジャージ',
                'page' => 1,
            ],
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(10, $target->refresh()->quantity);
        $this->assertSame(2, $other->refresh()->quantity);
    }

    #[Test]
    public function 表示中に含まれない在庫を送るとエラーになり更新されない(): void
    {
        $shown = $this->makeStock('チームジャージ', 1);
        $hidden = $this->makeStock('ロードスター', 2);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.stocks.bulk-update'),
            [
                // 絞り込み結果は「チームジャージ」の1件だけなので、混ぜた1件が検出される
                'stocks' => [
                    ['id' => $shown->id, 'quantity' => 10],
                    ['id' => $hidden->id, 'quantity' => 99],
                ],
                'q' => 'ジャージ',
                'page' => 1,
            ],
        );

        $response->assertSessionHasErrors('stocks');
        $this->assertSame(1, $shown->refresh()->quantity);
        $this->assertSame(2, $hidden->refresh()->quantity);
    }

    #[Test]
    public function 表示中の一部だけを送るとエラーになり更新されない(): void
    {
        $first = $this->makeStock('アクセサリー', 1);
        $second = $this->makeStock('ロードスター', 2);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.stocks.bulk-update'),
            [
                'stocks' => [['id' => $first->id, 'quantity' => 10]],
                'page' => 1,
            ],
        );

        $response->assertSessionHasErrors('stocks');
        $this->assertSame(1, $first->refresh()->quantity);
        $this->assertSame(2, $second->refresh()->quantity);
    }

    #[Test]
    public function 一部に不正な在庫数があれば全件更新されない(): void
    {
        $first = $this->makeStock('アクセサリー', 1);
        $second = $this->makeStock('ロードスター', 2);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.stocks.bulk-update'),
            [
                'stocks' => [
                    ['id' => $first->id, 'quantity' => 10],
                    ['id' => $second->id, 'quantity' => -5],
                ],
                'page' => 1,
            ],
        );

        $response->assertSessionHasErrors('stocks.1.quantity');
        $this->assertSame(1, $first->refresh()->quantity);
        $this->assertSame(2, $second->refresh()->quantity);
    }

    #[Test]
    public function 存在しない在庫を送ると更新できない(): void
    {
        $stock = $this->makeStock('アクセサリー', 1);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.stocks.bulk-update'),
            [
                'stocks' => [
                    ['id' => $stock->id, 'quantity' => 10],
                    ['id' => 99999, 'quantity' => 20],
                ],
                'page' => 1,
            ],
        );

        $response->assertSessionHasErrors('stocks.1.id');
        $this->assertSame(1, $stock->refresh()->quantity);
    }

    #[Test]
    public function 空の一括更新は受け付けない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.bulk-update'), ['stocks' => [], 'page' => 1])
            ->assertSessionHasErrors('stocks');
    }

    #[Test]
    public function 未認証は一括更新できない(): void
    {
        $stock = $this->makeStock('アクセサリー', 1);

        $this->put(route('admin.stocks.bulk-update'), [
            'stocks' => [['id' => $stock->id, 'quantity' => 10]],
            'page' => 1,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(1, $stock->refresh()->quantity);
    }
}
