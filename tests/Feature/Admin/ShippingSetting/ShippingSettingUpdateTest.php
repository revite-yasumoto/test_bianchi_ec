<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\ShippingSetting;

use App\Models\Admin;
use App\Models\ShippingSetting;
use Database\Seeders\PrefectureSeeder;
use Database\Seeders\ShippingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShippingSettingUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->seed(PrefectureSeeder::class);
        $this->seed(ShippingSettingSeeder::class);
    }

    /**
     * 画面が送信するのと同じ47件の配列を、現在の設定から組み立てる。
     *
     * @return array<int, array{id: int, fee: int, delivery_days: int}>
     */
    private function payload(int $fee = 800, int $deliveryDays = 4): array
    {
        return ShippingSetting::query()
            ->orderBy('prefecture_id')
            ->get()
            ->map(fn (ShippingSetting $setting): array => [
                'id' => $setting->id,
                'fee' => $fee,
                'delivery_days' => $deliveryDays,
            ])
            ->all();
    }

    #[Test]
    public function 全47件の送料と配送予定日数が一括更新される(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.shipping-settings.update'), ['settings' => $this->payload()]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(47, ShippingSetting::query()->where('fee', 800)->where('delivery_days', 4)->count());
    }

    #[Test]
    public function 要素数が47件でないリクエストは弾かれる(): void
    {
        $settings = $this->payload();
        array_pop($settings);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.shipping-settings.update'), ['settings' => $settings]);

        $response->assertSessionHasErrors('settings');
        $this->assertSame(0, ShippingSetting::query()->where('fee', 800)->count());
    }

    #[Test]
    public function 上限を超える送料は弾かれる(): void
    {
        $settings = $this->payload();
        $settings[0]['fee'] = 100001;

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.shipping-settings.update'), ['settings' => $settings]);

        $response->assertSessionHasErrors('settings.0.fee');
    }

    #[Test]
    public function 範囲外の配送予定日数は弾かれる(): void
    {
        $settings = $this->payload();
        $settings[0]['delivery_days'] = 0;

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.shipping-settings.update'), ['settings' => $settings]);

        $response->assertSessionHasErrors('settings.0.delivery_days');
    }

    #[Test]
    public function 一件でも不正な値があれば他の46件も更新されない(): void
    {
        $settings = $this->payload();
        $settings[0]['fee'] = -1;

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.shipping-settings.update'), ['settings' => $settings])
            ->assertSessionHasErrors('settings.0.fee');

        $this->assertSame(0, ShippingSetting::query()->where('delivery_days', 4)->count());
    }

    #[Test]
    public function 存在しない送料設定のidは弾かれる(): void
    {
        $settings = $this->payload();
        $settings[0]['id'] = 9999;

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.shipping-settings.update'), ['settings' => $settings])
            ->assertSessionHasErrors('settings.0.id');
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->put(route('admin.shipping-settings.update'), ['settings' => $this->payload()])
            ->assertRedirect(route('admin.login'));
    }
}
