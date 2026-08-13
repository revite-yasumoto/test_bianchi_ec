<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Checkout;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

class CheckoutIndexTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    private Prefecture $tokyo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        EcSetting::factory()->create();
        $this->tokyo = $this->createPrefectureWithShipping('東京都', 500, 3);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function カートが空のときは購入手続きに入れずカートへ戻される(): void
    {
        $this->actingAs($this->user)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'カートに商品がありません。');
    }

    #[Test]
    public function 在庫が不足している明細があるときは購入手続きに入れない(): void
    {
        $variant = $this->createVariantWithStock(3000, 1);
        $this->addToCart($this->user, $variant, 2);

        $this->actingAs($this->user)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    #[Test]
    public function 在庫切れの明細があるときは購入手続きに入れない(): void
    {
        $variant = $this->createVariantWithStock(3000, 1);
        $this->addToCart($this->user, $variant, 1);
        Stock::query()->where('product_variant_id', $variant->id)->update(['quantity' => 0]);

        $this->actingAs($this->user)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'));
    }

    #[Test]
    public function 登録済みの配送先が既定を先頭にして一覧される(): void
    {
        $osaka = $this->createPrefectureWithShipping('大阪府', 500, 2);
        UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
            'label' => '勤務先',
            'is_default' => false,
        ]);
        $default = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $osaka->id,
            'label' => '自宅',
            'is_default' => true,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->get(route('checkout.index'))
            ->assertInertia(fn ($page) => $page
                ->component('front/Checkout/Index')
                ->has('addresses', 2)
                ->where('addresses.0.id', $default->id)
                ->where('addresses.0.prefecture_name', '大阪府')
                ->where('addresses.1.label', '勤務先')
                ->where('selected.address_id', $default->id)
                ->where('selected.payment_method', 'bank_transfer')
            );
    }

    #[Test]
    public function 他人の配送先は一覧されない(): void
    {
        UserAddress::factory()->create([
            'user_id' => User::factory()->create()->id,
            'prefecture_id' => $this->tokyo->id,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->get(route('checkout.index'))
            ->assertInertia(fn ($page) => $page->has('addresses', 0));
    }

    #[Test]
    public function 都道府県ごとの送料とお届け日数が渡される(): void
    {
        $hokkaido = $this->createPrefectureWithShipping('北海道', 1000, 4);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->get(route('checkout.index'))
            ->assertInertia(fn ($page) => $page
                ->where('shippingByPrefecture.'.$this->tokyo->id.'.fee', 500)
                ->where('shippingByPrefecture.'.$this->tokyo->id.'.delivery_days', 3)
                ->where('shippingByPrefecture.'.$hokkaido->id.'.fee', 1000)
                ->where('shippingByPrefecture.'.$hokkaido->id.'.delivery_days', 4)
                ->has('prefectures', 2)
                ->where('ecSetting.free_shipping_threshold', 10000)
                ->where('ecSetting.cod_fee', 330)
                ->where('deliveryBaseDate', now()->toDateString())
            );
    }

    #[Test]
    public function セッションで選択済みの配送先と支払い方法が初期選択になる(): void
    {
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
            'is_default' => false,
        ]);
        UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
            'is_default' => true,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($address, PaymentMethod::Cod))
            ->get(route('checkout.index'))
            ->assertInertia(fn ($page) => $page
                ->where('selected.address_id', $address->id)
                ->where('selected.payment_method', 'cod')
            );
    }
}
