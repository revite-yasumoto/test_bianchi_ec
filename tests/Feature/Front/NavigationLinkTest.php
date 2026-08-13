<?php

declare(strict_types=1);

namespace Tests\Feature\Front;

use App\Models\EcSetting;
use App\Models\Prefecture;
use App\Models\ShippingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ヘッダー・フッターのリンク切れを検出する。
 * 対象のルート名は `resources/js/front/Components/NavMenu.ts` の定義に合わせて維持する。
 */
class NavigationLinkTest extends TestCase
{
    use RefreshDatabase;

    /** 未ログインで開けるリンク先 */
    private const GUEST_ROUTES = [
        'products.index',
        'news.index',
        'notices.index',
        'guide',
        'contact',
        'legal.tokushoho',
        'legal.privacy',
        'legal.terms',
    ];

    /** ログインが必要なリンク先 */
    private const AUTH_ROUTES = [
        'cart.index',
        'mypage.index',
        'mypage.favorites',
    ];

    /** カート・買い物ガイドは送料設定を参照するため、全ページ共通の前提として用意する */
    private function prepareSettings(): void
    {
        EcSetting::factory()->create();
        ShippingSetting::factory()->create([
            'prefecture_id' => Prefecture::factory()->create(['name' => '東京都'])->id,
        ]);
    }

    #[Test]
    public function ヘッダーとフッターのリンク先ルートがすべて定義されている(): void
    {
        foreach ([...self::GUEST_ROUTES, ...self::AUTH_ROUTES] as $name) {
            $this->assertTrue(
                Route::has($name),
                "ナビゲーションのリンク先ルート [{$name}] が定義されていません。",
            );
        }
    }

    #[Test]
    public function 未ログインで開けるリンク先がすべて表示できる(): void
    {
        $this->prepareSettings();

        foreach (self::GUEST_ROUTES as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    #[Test]
    public function ログインが必要なリンク先がすべて表示できる(): void
    {
        $this->prepareSettings();
        $user = User::factory()->create();

        foreach (self::AUTH_ROUTES as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }
}
