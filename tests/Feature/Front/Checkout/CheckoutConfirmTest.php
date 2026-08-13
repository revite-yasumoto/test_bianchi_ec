<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Checkout;

use App\Enums\PaymentMethod;
use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesCheckoutScenario;
use Tests\TestCase;

class CheckoutConfirmTest extends TestCase
{
    use CreatesCheckoutScenario, RefreshDatabase;

    private User $user;

    private Prefecture $tokyo;

    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        EcSetting::factory()->create(['bank_transfer_note' => 'ビアンキ銀行 渋谷支店 1234567']);
        $this->tokyo = $this->createPrefectureWithShipping('東京都', 500);
        $this->address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->tokyo->id,
        ]);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('checkout.confirm'))->assertRedirect(route('login'));
    }

    #[Test]
    public function セッションを持たずに開くと購入手続きへ戻される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->get(route('checkout.confirm'))
            ->assertRedirect(route('checkout.index'));
    }

    #[Test]
    public function 支払い方法だけがセッションにあるときは購入手続きへ戻される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->withSession(['checkout.payment_method' => PaymentMethod::Cod->value])
            ->get(route('checkout.confirm'))
            ->assertRedirect(route('checkout.index'));
    }

    #[Test]
    public function 他人の配送先を選択済みにしていても購入手続きへ戻される(): void
    {
        $othersAddress = UserAddress::factory()->create([
            'user_id' => User::factory()->create()->id,
            'prefecture_id' => $this->tokyo->id,
        ]);
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($othersAddress, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertRedirect(route('checkout.index'));
    }

    #[Test]
    public function カートが空になっていたらカートへ戻される(): void
    {
        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertRedirect(route('cart.index'));
    }

    #[Test]
    public function 配送先と支払い方法と商品明細が表示される(): void
    {
        $variant = $this->createVariantWithStock(3000, 10, ['name' => 'テストジャージ']);
        $this->addToCart($this->user, $variant, 2);

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertInertia(fn ($page) => $page
                ->component('front/Checkout/Confirm')
                ->where('address.id', $this->address->id)
                ->where('address.prefecture_name', '東京都')
                ->where('paymentMethod', 'bank_transfer')
                ->has('items', 1)
                ->where('items.0.product_name', 'テストジャージ')
                ->where('items.0.quantity', 2)
                ->where('items.0.subtotal', 6000)
            );
    }

    #[Test]
    public function 銀行振込のときは振込案内文が渡される(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::BankTransfer))
            ->get(route('checkout.confirm'))
            ->assertInertia(fn ($page) => $page
                ->where('bankTransferNote', 'ビアンキ銀行 渋谷支店 1234567')
            );
    }

    #[Test]
    public function 代引きのときは振込案内文が渡されない(): void
    {
        $this->addToCart($this->user, $this->createVariantWithStock());

        $this->actingAs($this->user)
            ->withSession($this->checkoutSession($this->address, PaymentMethod::Cod))
            ->get(route('checkout.confirm'))
            ->assertInertia(fn ($page) => $page->where('bankTransferNote', null));
    }

    #[Test]
    public function 他人の配送先を送信すると購入手続きの保存が拒否される(): void
    {
        $othersAddress = UserAddress::factory()->create([
            'user_id' => User::factory()->create()->id,
            'prefecture_id' => $this->tokyo->id,
        ]);

        $this->actingAs($this->user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'address_id' => $othersAddress->id,
                'payment_method' => PaymentMethod::BankTransfer->value,
            ])
            ->assertSessionHasErrors('address_id');
    }

    #[Test]
    public function 支払い方法に未定義の値を送信すると拒否される(): void
    {
        $this->actingAs($this->user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'address_id' => $this->address->id,
                'payment_method' => 'credit_card',
            ])
            ->assertSessionHasErrors('payment_method');
    }

    #[Test]
    public function 選択内容を保存すると注文確認へ遷移しセッションに保持される(): void
    {
        $this->actingAs($this->user)
            ->post(route('checkout.store'), [
                'address_id' => $this->address->id,
                'payment_method' => PaymentMethod::Cod->value,
            ])
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHas('checkout.address_id', $this->address->id)
            ->assertSessionHas('checkout.payment_method', 'cod');
    }
}
