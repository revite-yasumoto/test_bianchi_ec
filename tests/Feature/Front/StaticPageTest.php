<?php

declare(strict_types=1);

namespace Tests\Feature\Front;

use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaticPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 未ログインで買い物ガイドを閲覧できる(): void
    {
        EcSetting::factory()->create(['free_shipping_threshold' => 10000, 'cod_fee' => 330]);
        $prefecture = Prefecture::factory()->create(['name' => '東京都']);
        ShippingSetting::factory()->create([
            'prefecture_id' => $prefecture->id,
            'fee' => 500,
            'delivery_days' => 3,
        ]);

        $this->get(route('guide'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Static/Guide')
                ->where('ecSetting.free_shipping_threshold', 10000)
                ->where('ecSetting.cod_fee', 330)
                ->has('shippingTable', 1)
                ->where('shippingTable.0.prefecture_name', '東京都')
                ->where('shippingTable.0.fee', 500)
                ->where('shippingTable.0.delivery_days', 3)
            );
    }

    #[Test]
    public function 買い物ガイドの送料案内は基本設定の現在値を反映する(): void
    {
        EcSetting::factory()->create(['free_shipping_threshold' => 20000, 'cod_fee' => 550]);

        $this->get(route('guide'))
            ->assertInertia(fn ($page) => $page
                ->where('ecSetting.free_shipping_threshold', 20000)
                ->where('ecSetting.cod_fee', 550)
            );
    }

    #[Test]
    public function 未ログインで特定商取引法に基づく表記を閲覧できる(): void
    {
        $this->get(route('legal.tokushoho'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('front/Static/Tokushoho'));
    }

    #[Test]
    public function 未ログインでプライバシーポリシーを閲覧できる(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('front/Static/Privacy'));
    }

    #[Test]
    public function 未ログインで利用規約を閲覧できる(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('front/Static/Terms'));
    }
}
