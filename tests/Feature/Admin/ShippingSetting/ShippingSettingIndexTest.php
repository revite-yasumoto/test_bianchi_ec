<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\ShippingSetting;

use App\Enums\Region;
use App\Models\Admin;
use Database\Seeders\PrefectureSeeder;
use Database\Seeders\ShippingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShippingSettingIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PrefectureSeeder::class);
        $this->seed(ShippingSettingSeeder::class);
    }

    #[Test]
    public function 送料設定マスタに47件が都道府県コードの昇順で表示される(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.shipping-settings.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/ShippingSetting/Index')
                ->has('settings', 47)
                ->where('settings.0.prefecture_name', '北海道')
                ->where('settings.12.prefecture_name', '東京都')
                ->where('settings.46.prefecture_name', '沖縄県')
            );
    }

    #[Test]
    public function 初期値として北海道と沖縄県は1000円その他は500円が表示される(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.shipping-settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('settings.0.fee', 1000)
                ->where('settings.0.delivery_days', 5)
                ->where('settings.46.fee', 1000)
                ->where('settings.46.delivery_days', 6)
                ->where('settings.12.fee', 500)
                ->where('settings.12.delivery_days', 3)
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.shipping-settings.index'))
            ->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function 各行に所属する地方が付与される(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.shipping-settings.index'))
            ->assertInertia(fn ($page) => $page
                // 先頭は北海道、13番目は東京（関東）、末尾は沖縄（九州・沖縄）
                ->where('settings.0.region', Region::Hokkaido->value)
                ->where('settings.12.region', Region::Kanto->value)
                ->where('settings.46.region', Region::Kyushu->value)
            );
    }

    #[Test]
    public function 地方の選択肢が八件渡される(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.shipping-settings.index'))
            ->assertInertia(fn ($page) => $page
                ->has('regions', 8)
                ->where('regions.0.label', '北海道')
                ->where('regions.7.label', '九州・沖縄')
            );
    }
}
