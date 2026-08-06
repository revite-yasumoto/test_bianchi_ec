<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\EcSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Prefecture;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 注文確定後に商品・会員・設定が変更されても、orders / order_items のスナップショット列が
 * 変わらないことを担保する（注文時スナップショット設計の中核テスト）。
 */
class OrderSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function order_customer_snapshot_survives_user_changes(): void
    {
        $user = User::factory()->create(['name' => '山田 太郎', 'email' => 'taro@example.com', 'tel' => '090-1111-2222']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_tel' => $user->tel,
        ]);

        $user->update(['name' => '山田 次郎', 'email' => 'jiro@example.com', 'tel' => '090-9999-8888']);

        $order->refresh();

        $this->assertSame('山田 太郎', $order->customer_name);
        $this->assertSame('taro@example.com', $order->customer_email);
        $this->assertSame('090-1111-2222', $order->customer_tel);
    }

    #[Test]
    public function order_item_snapshot_survives_product_price_and_name_changes(): void
    {
        $category = Category::factory()->create(['name' => 'ロードバイク']);
        $product = Product::factory()->create([
            'name' => 'ROADSTER RC7 / 105',
            'category_id' => $category->id,
            'price' => 398000,
        ]);
        $order = Order::factory()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_name' => $category->name,
            'unit_price' => $product->price,
        ]);

        $product->update(['name' => 'ROADSTER RC9 / DURA-ACE', 'price' => 550000]);
        $category->update(['name' => 'プレミアムロード']);

        $item->refresh();

        $this->assertSame('ROADSTER RC7 / 105', $item->product_name);
        $this->assertSame('ロードバイク', $item->category_name);
        $this->assertSame(398000, $item->unit_price);
    }

    #[Test]
    public function order_item_survives_product_deletion_with_product_id_set_null(): void
    {
        $product = Product::factory()->create(['name' => '削除予定商品']);
        $order = Order::factory()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);

        $product->delete();

        $item->refresh();

        $this->assertNull($item->product_id);
        $this->assertSame('削除予定商品', $item->product_name);
        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
    }

    #[Test]
    public function order_snapshot_survives_shipping_and_ec_setting_changes(): void
    {
        $prefecture = Prefecture::factory()->create(['name' => '大阪府']);
        $shipping = ShippingSetting::factory()->create([
            'prefecture_id' => $prefecture->id,
            'fee' => 500,
            'delivery_days' => 3,
        ]);
        $ecSetting = EcSetting::factory()->create(['free_shipping_threshold' => 10000]);

        $order = Order::factory()->create([
            'shipping_prefecture_name' => $prefecture->name,
            'shipping_fee_base' => $shipping->fee,
            'delivery_days' => $shipping->delivery_days,
            'free_shipping_threshold' => $ecSetting->free_shipping_threshold,
        ]);

        $shipping->update(['fee' => 2000, 'delivery_days' => 10]);
        $ecSetting->update(['free_shipping_threshold' => 50000]);

        $order->refresh();

        $this->assertSame(500, $order->shipping_fee_base);
        $this->assertSame(3, $order->delivery_days);
        $this->assertSame(10000, $order->free_shipping_threshold);
    }

    #[Test]
    public function order_user_cannot_be_deleted_while_orders_exist(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        $user->delete();
    }
}
