<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Order;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Prefecture;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

/**
 * 注文確定後は `orders` / `order_items` の値を一切変更しない、という中核の業務ルールを担保する。
 * 参照元（商品・会員・住所・送料設定・EC基本設定）を後から変更しても注文の内容が変わらないことを検証する。
 */
class OrderSnapshotTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    private Prefecture $tokyo;

    private UserAddress $address;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => '架空 太郎',
            'name_kana' => 'カクウ タロウ',
            'email' => 'kakuu@example.test',
            'tel' => '090-0000-0000',
        ]);
        EcSetting::factory()->create([
            'free_shipping_threshold' => 10000,
            'cod_fee' => 330,
            'bank_transfer_note' => '注文時点の振込案内文',
        ]);
        $this->tokyo = $this->createPrefectureWithShipping('東京都', 500, 3);
        $this->address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
            'recipient_name' => '架空 太郎',
            'postal_code' => '150-0041',
            'city' => '渋谷区',
            'address_line1' => '架空町1-2-3',
            'address_line2' => '架空レジデンス404',
            'tel' => '090-0000-0000',
        ]);

        $this->variant = $this->createVariantWithStock(3000, 10, [
            'name' => '架空ジャージ',
            'product_code' => 'DUMMY-001',
        ], [
            'sku_code' => 'DUMMY-001-11',
            'size_name' => 'M',
            'color_name' => 'レッド',
        ]);
        $this->product = $this->variant->product;
        ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'path' => 'products/dummy-main.jpg',
            'sort_order' => 0,
        ]);
    }

    private function placeOrder(PaymentMethod $paymentMethod = PaymentMethod::BankTransfer): Order
    {
        $this->addToCart($this->user, $this->variant, 2);

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, $paymentMethod))
            ->post(route('orders.store'));

        return Order::query()->sole();
    }

    #[Test]
    public function 会員情報が注文にスナップショットされる(): void
    {
        $order = $this->placeOrder();

        $this->assertSame($this->user->member_code, $order->member_code_snapshot);
        $this->assertSame('架空 太郎', $order->customer_name);
        $this->assertSame('カクウ タロウ', $order->customer_name_kana);
        $this->assertSame('kakuu@example.test', $order->customer_email);
        $this->assertSame('090-0000-0000', $order->customer_tel);
    }

    #[Test]
    public function 配送先が都道府県名を含めて注文にスナップショットされる(): void
    {
        $order = $this->placeOrder();

        $this->assertSame('架空 太郎', $order->shipping_recipient_name);
        $this->assertSame('150-0041', $order->shipping_postal_code);
        $this->assertSame('東京都', $order->shipping_prefecture_name);
        $this->assertSame('渋谷区', $order->shipping_city);
        $this->assertSame('架空町1-2-3', $order->shipping_address_line1);
        $this->assertSame('架空レジデンス404', $order->shipping_address_line2);
        $this->assertSame('090-0000-0000', $order->shipping_tel);
    }

    #[Test]
    public function 金額の算出根拠が注文にスナップショットされる(): void
    {
        $order = $this->placeOrder();

        $this->assertSame(10000, $order->free_shipping_threshold);
        $this->assertSame(500, $order->shipping_fee_base);
        $this->assertSame(3, $order->delivery_days);
        $this->assertSame(now()->addDays(3)->toDateString(), $order->estimated_delivery_date->toDateString());
        $this->assertSame('注文時点の振込案内文', $order->bank_transfer_note);
    }

    #[Test]
    public function 商品情報が明細にスナップショットされる(): void
    {
        $order = $this->placeOrder();

        /** @var OrderItem $item */
        $item = $order->items()->sole();

        $this->assertSame($this->product->id, $item->product_id);
        $this->assertSame($this->variant->id, $item->product_variant_id);
        $this->assertSame('DUMMY-001', $item->product_code);
        $this->assertSame('架空ジャージ', $item->product_name);
        $this->assertSame($this->product->category->name, $item->category_name);
        $this->assertSame('DUMMY-001-11', $item->sku_code);
        $this->assertSame('M', $item->size_name);
        $this->assertSame('レッド', $item->color_name);
        $this->assertSame('products/dummy-main.jpg', $item->product_image_path);
        $this->assertSame(3000, $item->unit_price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(6000, $item->subtotal);
    }

    #[Test]
    public function 商品価格を変更しても注文の単価と金額は変わらない(): void
    {
        $order = $this->placeOrder();

        $this->product->update(['price' => 9800]);

        $order->refresh();
        $this->assertSame(3000, $order->items()->sole()->unit_price);
        $this->assertSame(6000, $order->items()->sole()->subtotal);
        $this->assertSame(6000, $order->subtotal);
        $this->assertSame(6500, $order->total);
    }

    #[Test]
    public function 商品名とカテゴリ名を変更しても明細の表示は変わらない(): void
    {
        $order = $this->placeOrder();

        $this->product->update(['name' => '改名後ジャージ']);
        $this->product->category->update(['name' => '改名後カテゴリ']);

        $this->assertSame('架空ジャージ', $order->items()->sole()->product_name);
        $this->assertNotSame('改名後カテゴリ', $order->items()->sole()->category_name);
    }

    #[Test]
    public function 会員情報を変更しても注文の会員スナップショットは変わらない(): void
    {
        $order = $this->placeOrder();

        $this->user->update([
            'name' => '変更後 太郎',
            'email' => 'changed@example.test',
            'tel' => '080-9999-9999',
        ]);

        $order->refresh();
        $this->assertSame('架空 太郎', $order->customer_name);
        $this->assertSame('kakuu@example.test', $order->customer_email);
        $this->assertSame('090-0000-0000', $order->customer_tel);
    }

    #[Test]
    public function 配送先住所を変更しても注文の配送先スナップショットは変わらない(): void
    {
        $order = $this->placeOrder();

        $this->address->update([
            'recipient_name' => '変更後 太郎',
            'city' => '港区',
            'address_line1' => '変更後1-1-1',
        ]);

        $order->refresh();
        $this->assertSame('架空 太郎', $order->shipping_recipient_name);
        $this->assertSame('渋谷区', $order->shipping_city);
        $this->assertSame('架空町1-2-3', $order->shipping_address_line1);
    }

    #[Test]
    public function 送料設定を変更しても注文の送料と配達予定日は変わらない(): void
    {
        $order = $this->placeOrder();
        $estimatedDeliveryDate = $order->estimated_delivery_date->toDateString();

        ShippingSetting::query()
            ->where('prefecture_id', $this->tokyo->id)
            ->update(['fee' => 1500, 'delivery_days' => 9]);

        $order->refresh();
        $this->assertSame(500, $order->shipping_fee);
        $this->assertSame(500, $order->shipping_fee_base);
        $this->assertSame(3, $order->delivery_days);
        $this->assertSame($estimatedDeliveryDate, $order->estimated_delivery_date->toDateString());
        $this->assertSame(6500, $order->total);
    }

    #[Test]
    public function 送料設定の都道府県名を変更しても注文の都道府県名は変わらない(): void
    {
        $order = $this->placeOrder();

        $this->tokyo->update(['name' => '改名後県']);

        $this->assertSame('東京都', $order->refresh()->shipping_prefecture_name);
    }

    #[Test]
    public function 基本設定を変更しても注文のしきい値と手数料と案内文は変わらない(): void
    {
        $order = $this->placeOrder();

        EcSetting::query()->find(1)?->update([
            'free_shipping_threshold' => 3000,
            'cod_fee' => 990,
            'bank_transfer_note' => '変更後の振込案内文',
        ]);

        $order->refresh();
        $this->assertSame(10000, $order->free_shipping_threshold);
        $this->assertSame(0, $order->cod_fee);
        $this->assertSame('注文時点の振込案内文', $order->bank_transfer_note);
    }

    #[Test]
    public function 商品を削除しても明細は残り商品への参照だけがなくなる(): void
    {
        $order = $this->placeOrder();

        $this->product->delete();

        /** @var OrderItem $item */
        $item = $order->items()->sole();
        $this->assertNull($item->product_id);
        $this->assertNull($item->product_variant_id);
        $this->assertSame('架空ジャージ', $item->product_name);
        $this->assertSame('DUMMY-001', $item->product_code);
        $this->assertSame(3000, $item->unit_price);
        $this->assertSame(6000, $item->subtotal);
    }
}
