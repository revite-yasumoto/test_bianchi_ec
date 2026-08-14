<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Address;

use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreUserAddressTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Prefecture $prefecture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->prefecture = Prefecture::factory()->create(['name' => '東京都']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'label' => '自宅',
            'recipient_name' => '架空 太郎',
            'postal_code' => '150-0041',
            'prefecture_id' => $this->prefecture->id,
            'city' => '渋谷区',
            'address_line1' => '架空町1-2-3',
            'address_line2' => '架空レジデンス404',
            'tel' => '090-0000-0000',
            ...$overrides,
        ];
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->post(route('addresses.store'), $this->payload())
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function 配送先が追加される(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload())
            ->assertSessionHas('success', 'お届け先を追加しました');

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'label' => '自宅',
            'recipient_name' => '架空 太郎',
            'postal_code' => '150-0041',
            'prefecture_id' => $this->prefecture->id,
            'city' => '渋谷区',
            'address_line1' => '架空町1-2-3',
            'address_line2' => '架空レジデンス404',
            'tel' => '090-0000-0000',
            'is_default' => false,
        ]);
    }

    #[Test]
    public function 建物名は省略できる(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['address_line2' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_addresses', ['address_line2' => null]);
    }

    #[Test]
    public function 既定に指定すると他の住所の既定が解除される(): void
    {
        $existing = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->prefecture->id,
            'is_default' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['is_default' => true]));

        $this->assertFalse($existing->refresh()->is_default);
        $this->assertSame(1, $this->user->addresses()->where('is_default', true)->count());
    }

    #[Test]
    public function 既定に指定しなければ他の住所の既定は変わらない(): void
    {
        $existing = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->prefecture->id,
            'is_default' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload());

        $this->assertTrue($existing->refresh()->is_default);
    }

    #[Test]
    public function 他の会員の既定は解除されない(): void
    {
        $other = User::factory()->create();
        $othersAddress = UserAddress::factory()->create([
            'user_id' => $other->id,
            'prefecture_id' => $this->prefecture->id,
            'is_default' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['is_default' => true]));

        $this->assertTrue($othersAddress->refresh()->is_default);
    }

    #[Test]
    public function 購入手続きから追加した住所は選択済みになる(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['use_for_checkout' => true]));

        $address = $this->user->addresses()->sole();
        $this->assertSame($address->id, session('checkout.address_id'));
    }

    #[Test]
    public function 購入手続き以外から追加した住所は選択済みにならない(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload());

        $this->assertNull(session('checkout.address_id'));
    }

    #[Test]
    public function 必須項目が未入力なら追加されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), [])
            ->assertSessionHasErrors([
                'label',
                'recipient_name',
                'postal_code',
                'prefecture_id',
                'city',
                'address_line1',
                'tel',
            ]);

        $this->assertDatabaseCount('user_addresses', 0);
    }

    #[Test]
    public function 郵便番号の形式が誤っていれば追加されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['postal_code' => '15-004']))
            ->assertSessionHasErrors('postal_code');

        $this->assertDatabaseCount('user_addresses', 0);
    }

    #[Test]
    public function ハイフンなしの郵便番号は受け付ける(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['postal_code' => '1500041']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_addresses', ['postal_code' => '1500041']);
    }

    #[Test]
    public function 電話番号に数字とハイフン以外が含まれていれば追加されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['tel' => '090-1234-567a']))
            ->assertSessionHasErrors('tel');
    }

    #[Test]
    public function 存在しない都道府県は追加されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['prefecture_id' => $this->prefecture->id + 99]))
            ->assertSessionHasErrors('prefecture_id');
    }

    #[Test]
    public function 表示名が上限を超えると追加されない(): void
    {
        $this->actingAs($this->user)
            ->post(route('addresses.store'), $this->payload(['label' => str_repeat('あ', 101)]))
            ->assertSessionHasErrors('label');
    }
}
