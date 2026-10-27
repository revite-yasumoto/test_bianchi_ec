<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Banner;

use App\Enums\BannerBackground;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 画像パスを持つバナーは Factory で用意する。画像を保存して作成する経路は BannerStoreTest が検証している。
 * 訴求商品の選択肢に出す商品も Factory で用意する。商品を登録する経路は Tests\Feature\Admin\Product\ProductStoreTest が検証している。
 */
class BannerIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function メインビジュアル管理画面に表示順で一覧が表示される(): void
    {
        $admin = Admin::factory()->create();
        $product = Product::factory()->create(['product_code' => 'RC7-105', 'name' => 'ロードスター']);
        $second = Banner::factory()->create(['sort_order' => 2, 'is_active' => false]);
        $first = Banner::factory()->create([
            'sort_order' => 1,
            'product_id' => $product->id,
            'background' => BannerBackground::Rose,
            'pc_image_path' => 'banners/pc.jpg',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.banners.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Banner/Index')
                ->has('banners', 2)
                ->where('banners.0.id', $first->id)
                ->where('banners.0.product_id', $product->id)
                ->where('banners.0.background', 'rose')
                ->where('banners.0.background_gradient', BannerBackground::Rose->gradient())
                ->where('banners.0.pc_image_url', asset('storage/banners/pc.jpg'))
                ->where('banners.0.sp_image_url', null)
                ->where('banners.0.state_label', '公開')
                ->where('banners.1.id', $second->id)
                ->where('banners.1.state_label', '非公開')
                ->has('backgroundOptions', count(BannerBackground::cases()))
                ->where('backgroundOptions.0', ['value' => 'blue', 'label' => 'ブルー'])
                ->where('productOptions', [['id' => $product->id, 'label' => 'RC7-105 ロードスター']])
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.banners.index'))
            ->assertRedirect(route('admin.login'));
    }
}
