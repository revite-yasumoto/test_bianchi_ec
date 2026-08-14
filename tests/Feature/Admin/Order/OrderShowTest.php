<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderShowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    #[Test]
    public function 注文詳細がスナップショット列で表示される(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'BNC-2607-0918',
            'customer_name' => '山田 太郎',
            'shipping_prefecture_name' => '東京都',
            'subtotal' => 18000,
            'shipping_fee' => 500,
            'total' => 18500,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'チームジャージ 2026',
            'category_name' => 'ウェア',
            'size_name' => 'M',
            'color_name' => 'ネイビー',
            'unit_price' => 9000,
            'quantity' => 2,
            'subtotal' => 18000,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Order/Show')
                ->where('order.order_number', 'BNC-2607-0918')
                ->where('order.customer.name', '山田 太郎')
                ->where('order.shipping.prefecture_name', '東京都')
                ->has('order.items', 1)
                ->where('order.items.0.product_name', 'チームジャージ 2026')
                ->where('order.items.0.variant_label', 'ネイビー / M')
                ->where('order.items.0.subtotal', 18000)
                ->where('order.total', 18500)
            );
    }

    #[Test]
    public function 規格のない明細は規格なしと表示される(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'size_name' => null,
            'color_name' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->where('order.items.0.variant_label', '規格なし')
            );
    }

    #[Test]
    public function 商品の名称や価格を変更しても注文詳細の表示は変わらない(): void
    {
        $category = Category::factory()->create(['name' => 'ウェア']);
        $product = Product::factory()->create([
            'name' => 'チームジャージ 2026',
            'category_id' => $category->id,
            'price' => 9000,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'チームジャージ 2026',
            'category_name' => 'ウェア',
            'unit_price' => 9000,
            'quantity' => 1,
            'subtotal' => 9000,
        ]);

        $product->update(['name' => 'チームジャージ 2027', 'price' => 12000]);
        $category->update(['name' => 'アパレル']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->where('order.items.0.product_name', 'チームジャージ 2026')
                ->where('order.items.0.category_name', 'ウェア')
                ->where('order.items.0.unit_price', 9000)
            );
    }

    #[Test]
    public function 商品を削除しても注文明細は表示される(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name' => 'チームジャージ 2026',
        ]);

        $product->delete();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->has('order.items', 1)
                ->where('order.items.0.product_name', 'チームジャージ 2026')
            );
    }

    #[Test]
    public function ステータス変更履歴が新しい順で表示される(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Preparing]);
        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'admin_id' => $this->admin->id,
            'from_status' => OrderStatus::Received,
            'to_status' => OrderStatus::PaymentConfirmed,
            'changed_at' => '2026-07-24 10:00:00',
        ]);
        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'admin_id' => $this->admin->id,
            'from_status' => OrderStatus::PaymentConfirmed,
            'to_status' => OrderStatus::Preparing,
            'changed_at' => '2026-07-25 10:00:00',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->has('order.histories', 2)
                ->where('order.histories.0.to_status_label', '出荷準備中')
                ->where('order.histories.0.from_status_label', '入金確認済み')
                ->where('order.histories.0.admin_name', $this->admin->name)
                ->where('order.histories.1.to_status_label', '入金確認済み')
            );
    }

    #[Test]
    public function 遷移できるステータスだけが選択肢になる(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Preparing]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->has('statusOptions', 2)
                ->where('statusOptions.0.value', OrderStatus::Shipped->value)
                ->where('statusOptions.1.value', OrderStatus::Cancelled->value)
            );
    }

    #[Test]
    public function 最終ステータスの注文には選択肢がない(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page->has('statusOptions', 0));
    }

    #[Test]
    public function 未認証は注文詳細を開けない(): void
    {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.show', $order))
            ->assertRedirect(route('admin.login'));
    }
}
