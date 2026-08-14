<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Order;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Order;
use App\Models\Prefecture;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

/**
 * 保存する金額はクライアント・セッションの値を使わず、注文確定時のサーバー側の再計算結果であることを担保する。
 */
class OrderAmountRecalculationTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    private Prefecture $tokyo;

    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        EcSetting::factory()->create([
            'free_shipping_threshold' => 10000,
            'cod_fee' => 330,
        ]);
        $this->tokyo = $this->createPrefectureWithShipping('東京都', 500, 3);
        $this->address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extraSession
     */
    private function placeOrder(array $extraSession = [], PaymentMethod $paymentMethod = PaymentMethod::BankTransfer): Order
    {
        $this->actingAs($this->user)
            ->withSession([...$this->checkoutSession($this->address, $paymentMethod), ...$extraSession])
            ->post(route('orders.store'));

        return Order::query()->sole();
    }

    #[Test]
    public function セッションに金額を混ぜても保存される金額はサーバーの再計算結果になる(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock(3000), 2);

        $order = $this->placeOrder([
            'checkout.subtotal' => 1,
            'checkout.shipping_fee' => 0,
            'checkout.total' => 1,
        ]);

        $this->assertSame(6000, $order->subtotal);
        $this->assertSame(500, $order->shipping_fee);
        $this->assertSame(6500, $order->total);
    }

    #[Test]
    public function リクエストで金額を送っても保存される金額は再計算結果になる(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock(3000), 1);

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::BankTransfer))
            ->post(route('orders.store'), [
                'subtotal' => 1,
                'shipping_fee' => 0,
                'cod_fee' => 0,
                'total' => 1,
            ]);

        $order = Order::query()->sole();
        $this->assertSame(3000, $order->subtotal);
        $this->assertSame(3500, $order->total);
    }

    #[Test]
    public function 注文確認の表示後に商品価格が変わったら確定時の価格で保存される(): void
    {
        $variant = $this->createVariantWithStock(3000);
        $this->addToCart($this->user, $variant, 1);

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertInertia(fn ($page) => $page->where('amounts.total', 3500));

        $variant->product->update(['price' => 12000]);

        $order = $this->placeOrder();
        $this->assertSame(12000, $order->subtotal);
        // 送料無料のしきい値を超えたため送料も再判定される
        $this->assertSame(0, $order->shipping_fee);
        $this->assertSame(12000, $order->total);
    }

    #[Test]
    public function 注文確認の表示後に送料設定が変わったら確定時の設定で保存される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock(3000), 1);

        ShippingSetting::query()
            ->where('prefecture_id', $this->tokyo->id)
            ->update(['fee' => 1200, 'delivery_days' => 5]);

        $order = $this->placeOrder();

        $this->assertSame(1200, $order->shipping_fee);
        $this->assertSame(1200, $order->shipping_fee_base);
        $this->assertSame(5, $order->delivery_days);
        $this->assertSame(4200, $order->total);
    }

    #[Test]
    public function 代引きの手数料も確定時の基本設定で再計算される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock(3000), 1);

        EcSetting::query()->find(1)?->update(['cod_fee' => 550]);

        $order = $this->placeOrder([], PaymentMethod::Cod);

        $this->assertSame(550, $order->cod_fee);
        $this->assertSame(4050, $order->total);
    }
}
