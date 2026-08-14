<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Checkout;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

/**
 * 注文確認画面で表示する金額はサーバー側の再計算結果である。
 * 購入手続き画面での即時再計算はこの結果と同じ規則をフロント側で適用したものにあたる。
 */
class CheckoutAmountTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        EcSetting::factory()->create([
            'free_shipping_threshold' => 10000,
            'cod_fee' => 330,
        ]);
    }

    private function confirmWith(string $prefectureName, int $fee, int $price, PaymentMethod $paymentMethod): TestResponse
    {
        $prefecture = $this->createPrefectureWithShipping($prefectureName, $fee);
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $prefecture->id,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock($price));

        return $this->actingAs($this->user)
            ->withSession($this->checkoutSession($address, $paymentMethod))
            ->get(route('checkout.confirm'));
    }

    #[Test]
    public function 北海道の配送先では送料が1000円になる(): void
    {
        $this->confirmWith('北海道', 1000, 3000, PaymentMethod::BankTransfer)
            ->assertInertia(fn ($page) => $page
                ->where('amounts.subtotal', 3000)
                ->where('amounts.shipping_fee', 1000)
                ->where('amounts.total', 4000)
            );
    }

    #[Test]
    public function 大阪府の配送先では送料が500円になる(): void
    {
        $this->confirmWith('大阪府', 500, 3000, PaymentMethod::BankTransfer)
            ->assertInertia(fn ($page) => $page
                ->where('amounts.shipping_fee', 500)
                ->where('amounts.total', 3500)
            );
    }

    #[Test]
    public function 商品合計がしきい値と同額なら送料が無料になる(): void
    {
        $this->confirmWith('北海道', 1000, 10000, PaymentMethod::BankTransfer)
            ->assertInertia(fn ($page) => $page
                ->where('amounts.shipping_fee', 0)
                ->where('amounts.total', 10000)
            );
    }

    #[Test]
    public function 代引きを選ぶと代引き手数料が加算される(): void
    {
        $this->confirmWith('大阪府', 500, 3000, PaymentMethod::Cod)
            ->assertInertia(fn ($page) => $page
                ->where('amounts.cod_fee', 330)
                ->where('amounts.total', 3830)
            );
    }

    #[Test]
    public function 銀行振込を選ぶと代引き手数料は0になる(): void
    {
        $this->confirmWith('大阪府', 500, 3000, PaymentMethod::BankTransfer)
            ->assertInertia(fn ($page) => $page
                ->where('amounts.cod_fee', 0)
                ->where('amounts.total', 3500)
            );
    }

    #[Test]
    public function 送料が無料でも代引き手数料は加算される(): void
    {
        $this->confirmWith('北海道', 1000, 10000, PaymentMethod::Cod)
            ->assertInertia(fn ($page) => $page
                ->where('amounts.shipping_fee', 0)
                ->where('amounts.cod_fee', 330)
                ->where('amounts.total', 10330)
            );
    }

    #[Test]
    public function 配達予定日は当日にお届け日数を暦日で加算した日付になる(): void
    {
        $prefecture = $this->createPrefectureWithShipping('北海道', 1000, 4);
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $prefecture->id,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($address, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertInertia(fn ($page) => $page
                ->where('amounts.estimated_delivery_date', now()->addDays(4)->toDateString())
            );
    }

    #[Test]
    public function 明細が複数あるときは商品合計が明細の小計の合計になる(): void
    {
        $prefecture = $this->createPrefectureWithShipping('東京都', 500);
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $prefecture->id,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock(3000), 2);
        $this->addToCart($this->user, $this->createVariantWithStock(1500), 1);

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($address, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertInertia(fn ($page) => $page
                ->where('amounts.subtotal', 7500)
                ->where('amounts.total', 8000)
            );
    }
}
