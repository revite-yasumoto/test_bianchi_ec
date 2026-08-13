<?php

declare(strict_types=1);

namespace Tests\Feature\Front\MyPage;

use App\Models\Order;
use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddressTest extends TestCase
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
            'address_line2' => null,
            'tel' => '090-0000-0000',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAddress(array $attributes = []): UserAddress
    {
        return UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'prefecture_id' => $this->prefecture->id,
            ...$attributes,
        ]);
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('mypage.addresses'))->assertRedirect(route('login'));
    }

    #[Test]
    public function 自分の配送先だけが一覧に表示される(): void
    {
        $this->createAddress(['label' => '自宅']);

        $other = User::factory()->create();
        UserAddress::factory()->create([
            'user_id' => $other->id,
            'prefecture_id' => $this->prefecture->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('mypage.addresses'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/MyPage/Addresses')
                ->has('addresses', 1)
                ->where('addresses.0.label', '自宅')
                ->where('addresses.0.prefecture_name', '東京都')
                ->has('prefectures', 1)
            );
    }

    #[Test]
    public function 既定の配送先が先頭に並ぶ(): void
    {
        $this->createAddress(['label' => '職場', 'is_default' => false]);
        $this->createAddress(['label' => '自宅', 'is_default' => true]);

        $this->actingAs($this->user)
            ->get(route('mypage.addresses'))
            ->assertInertia(fn ($page) => $page->where('addresses.0.label', '自宅'));
    }

    #[Test]
    public function 配送先を更新できる(): void
    {
        $address = $this->createAddress(['label' => '自宅']);

        $this->actingAs($this->user)
            ->put(route('addresses.update', [$address]), $this->payload(['label' => '実家']))
            ->assertSessionHas('success', 'お届け先を更新しました');

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => '実家',
            'recipient_name' => '架空 太郎',
            'city' => '渋谷区',
        ]);
    }

    #[Test]
    public function 更新で既定に指定すると他の住所の既定が解除される(): void
    {
        $existing = $this->createAddress(['is_default' => true]);
        $target = $this->createAddress(['is_default' => false]);

        $this->actingAs($this->user)
            ->put(route('addresses.update', [$target]), $this->payload(['is_default' => true]));

        $this->assertFalse($existing->refresh()->is_default);
        $this->assertTrue($target->refresh()->is_default);
    }

    #[Test]
    public function 他人の配送先は更新できない(): void
    {
        $other = User::factory()->create();
        $othersAddress = UserAddress::factory()->create([
            'user_id' => $other->id,
            'prefecture_id' => $this->prefecture->id,
            'label' => '他人の住所',
        ]);

        $this->actingAs($this->user)
            ->put(route('addresses.update', [$othersAddress]), $this->payload(['label' => '書き換え']))
            ->assertForbidden();

        $this->assertSame('他人の住所', $othersAddress->refresh()->label);
    }

    #[Test]
    public function 入力に不備があれば更新されない(): void
    {
        $address = $this->createAddress(['label' => '自宅']);

        $this->actingAs($this->user)
            ->put(route('addresses.update', [$address]), $this->payload(['postal_code' => '15-004']))
            ->assertSessionHasErrors('postal_code');

        $this->assertSame('自宅', $address->refresh()->label);
    }

    #[Test]
    public function 配送先を削除できる(): void
    {
        $address = $this->createAddress();

        $this->actingAs($this->user)
            ->delete(route('addresses.destroy', [$address]))
            ->assertSessionHas('success', 'お届け先を削除しました');

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    #[Test]
    public function 配送先が一件だけでも削除できる(): void
    {
        $address = $this->createAddress();

        $this->actingAs($this->user)->delete(route('addresses.destroy', [$address]));

        $this->assertSame(0, $this->user->addresses()->count());
    }

    #[Test]
    public function 他人の配送先は削除できない(): void
    {
        $other = User::factory()->create();
        $othersAddress = UserAddress::factory()->create([
            'user_id' => $other->id,
            'prefecture_id' => $this->prefecture->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('addresses.destroy', [$othersAddress]))
            ->assertForbidden();

        $this->assertDatabaseHas('user_addresses', ['id' => $othersAddress->id]);
    }

    #[Test]
    public function 配送先を削除しても確定済みの注文のお届け先は変わらない(): void
    {
        $address = $this->createAddress();
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'shipping_recipient_name' => '架空 太郎',
            'shipping_city' => '渋谷区',
        ]);

        $this->actingAs($this->user)->delete(route('addresses.destroy', [$address]));

        $order->refresh();
        $this->assertSame('架空 太郎', $order->shipping_recipient_name);
        $this->assertSame('渋谷区', $order->shipping_city);
    }
}
