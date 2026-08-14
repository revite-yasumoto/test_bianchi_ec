<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Stock;

use App\Models\Admin;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->stock = Stock::factory()->create(['quantity' => 5]);
    }

    #[Test]
    public function 在庫数を更新できる(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.update', $this->stock), ['quantity' => 42]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(42, $this->stock->refresh()->quantity);
    }

    #[Test]
    public function 在庫数を0に更新できる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.update', $this->stock), ['quantity' => 0]);

        $this->assertSame(0, $this->stock->refresh()->quantity);
    }

    #[Test]
    public function 負の在庫数は更新できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.update', $this->stock), ['quantity' => -1]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(5, $this->stock->refresh()->quantity);
    }

    #[Test]
    public function 上限を超える在庫数は更新できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.update', $this->stock), ['quantity' => 1000000]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(5, $this->stock->refresh()->quantity);
    }

    #[Test]
    public function 数値以外の在庫数は更新できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.update', $this->stock), ['quantity' => 'abc']);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(5, $this->stock->refresh()->quantity);
    }

    #[Test]
    public function 在庫数が未入力では更新できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stocks.update', $this->stock), []);

        $response->assertSessionHasErrors('quantity');
    }

    #[Test]
    public function 未認証は在庫を更新できない(): void
    {
        $this->put(route('admin.stocks.update', $this->stock), ['quantity' => 42])
            ->assertRedirect(route('admin.login'));

        $this->assertSame(5, $this->stock->refresh()->quantity);
    }
}
