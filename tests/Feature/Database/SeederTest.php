<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\EcSetting;
use App\Models\Order;
use App\Models\Prefecture;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\EcSettingSeeder;
use Database\Seeders\PrefectureSeeder;
use Database\Seeders\ShippingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function prefecture_seeder_creates_47_prefectures(): void
    {
        $this->seed(PrefectureSeeder::class);

        $this->assertDatabaseCount('prefectures', 47);
        $this->assertSame('北海道', Prefecture::query()->first()?->name);
    }

    #[Test]
    public function shipping_setting_seeder_creates_47_rows_with_correct_initial_values(): void
    {
        $this->seed(PrefectureSeeder::class);
        $this->seed(ShippingSettingSeeder::class);

        $this->assertDatabaseCount('shipping_settings', 47);

        $hokkaido = Prefecture::query()->where('name', '北海道')->first();
        $setting = ShippingSetting::query()->where('prefecture_id', $hokkaido->id)->first();
        $this->assertSame(1000, $setting->fee);
        $this->assertSame(5, $setting->delivery_days);

        $tokyo = Prefecture::query()->where('name', '東京都')->first();
        $setting = ShippingSetting::query()->where('prefecture_id', $tokyo->id)->first();
        $this->assertSame(500, $setting->fee);
        $this->assertSame(3, $setting->delivery_days);
    }

    #[Test]
    public function ec_setting_seeder_creates_a_single_row_with_initial_values(): void
    {
        $this->seed(EcSettingSeeder::class);

        $this->assertDatabaseCount('ec_settings', 1);
        $setting = EcSetting::query()->first();
        $this->assertSame(10000, $setting->free_shipping_threshold);
        $this->assertSame(330, $setting->cod_fee);
    }

    #[Test]
    public function admin_seeder_creates_four_admins(): void
    {
        $this->seed(AdminSeeder::class);

        $this->assertDatabaseCount('admins', 4);
        $this->assertTrue(Admin::query()->where('admin_code', 'A-001')->exists());
    }

    #[Test]
    public function full_database_seeder_runs_without_error_and_covers_all_order_statuses(): void
    {
        $this->seed();

        $this->assertDatabaseCount('prefectures', 47);
        $this->assertDatabaseCount('shipping_settings', 47);
        $this->assertGreaterThanOrEqual(6, User::query()->count());
        $this->assertGreaterThanOrEqual(9, Product::query()->count());

        $statuses = Order::query()->pluck('status')->map(fn ($status) => $status->value)->unique()->sort()->values();
        $expected = collect(OrderStatus::cases())->map(fn ($status) => $status->value)->sort()->values();

        $this->assertEquals($expected->all(), $statuses->all());
    }

    #[Test]
    public function seeders_are_idempotent_when_run_twice(): void
    {
        $this->seed(PrefectureSeeder::class);
        $this->seed(PrefectureSeeder::class);

        $this->assertDatabaseCount('prefectures', 47);
    }
}
