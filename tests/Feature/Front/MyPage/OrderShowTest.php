<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $this->get(route('mypage.orders.show', [$order]))->assertRedirect(route('login'));
    }

    #[Test]
    public function 自分の注文の詳細が表示される(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'BNC-0001-0001',
            'shipping_recipient_name' => '架空 太郎',
            'shipping_city' => '渋谷区',
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => '架空ジャージ',
            'color_name' => 'ブラック',
            'size_name' => 'M',
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/MyPage/OrderShow')
                ->where('order.order_number', 'BNC-0001-0001')
                ->where('order.shipping.recipient_name', '架空 太郎')
                ->where('order.shipping.city', '渋谷区')
                ->has('order.items', 1)
                ->where('order.items.0.product_name', '架空ジャージ')
                ->where('order.items.0.variant_label', 'ブラック / M')
            );
    }

    #[Test]
    public function 他人の注文は閲覧できない(): void
    {
        $other = User::factory()->create();
        $othersOrder = Order::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$othersOrder]))
            ->assertForbidden();
    }

    #[Test]
    public function 明細はスナップショットで描画され商品を削除しても残る(): void
    {
        $product = Product::factory()->create(['name' => '削除前の商品名']);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '注文時の商品名',
        ]);

        $product->delete();

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('order.items', 1)
                ->where('order.items.0.product_name', '注文時の商品名')
            );
    }

    #[Test]
    public function 規格を持たない明細は規格なしと表示される(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'color_name' => null,
            'size_name' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertInertia(fn ($page) => $page->where('order.items.0.variant_label', '規格なし'));
    }

    #[Test]
    public function 銀行振込の注文には振込案内文が表示される(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_method' => PaymentMethod::BankTransfer,
            'bank_transfer_note' => '架空銀行 架空支店 普通 0000000',
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertInertia(fn ($page) => $page
                ->where('order.payment_method_label', '銀行振込（前払い）')
                ->where('order.bank_transfer_note', '架空銀行 架空支店 普通 0000000')
            );
    }

    #[Test]
    public function 代引きの注文には振込案内文が表示されない(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_method' => PaymentMethod::Cod,
            'bank_transfer_note' => null,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertInertia(fn ($page) => $page
                ->where('order.payment_method_label', '代金引換')
                ->where('order.bank_transfer_note', null)
            );
    }

    #[Test]
    public function 公開中の商品の明細には商品詳細へのリンクが付く(): void
    {
        $product = Product::factory()->create(['is_published' => true]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertInertia(fn ($page) => $page
                ->where('order.items.0.product_url', route('products.show', $product))
            );
    }

    #[Test]
    public function 非公開になった商品の明細にはリンクが付かない(): void
    {
        $product = Product::factory()->create(['is_published' => false]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertInertia(fn ($page) => $page->where('order.items.0.product_url', null));
    }

    #[Test]
    public function 削除済みの商品の明細にはリンクが付かない(): void
    {
        $product = Product::factory()->create(['is_published' => true]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
        ]);

        $product->delete();

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertInertia(fn ($page) => $page
                ->where('order.items.0.product_url', null)
                // スナップショットの商品名は残る
                ->where('order.items.0.product_name', $item->product_name)
            );
    }

    #[Test]
    public function 明細が増えても注文詳細のクエリ数は変わらない(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
        ]);

        $baseline = $this->countQueriesOfShow($order);

        for ($index = 0; $index < 4; $index++) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => Product::factory()->create()->id,
            ]);
        }

        $this->assertSame($baseline, $this->countQueriesOfShow($order));
    }

    private function countQueriesOfShow(Order $order): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->actingAs($this->user)
            ->get(route('mypage.orders.show', [$order]))
            ->assertOk();

        return $count;
    }
}
