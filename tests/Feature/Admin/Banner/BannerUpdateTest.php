<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Banner;

use App\Enums\BannerBackground;
use App\Models\Admin;
use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesImageUpload;
use Tests\TestCase;

/**
 * 画像を持つ既存バナーは Factory と偽のストレージで用意する。画像を保存して作成する経路は BannerStoreTest が検証している。
 * 作成・更新で共通の入力ルール（SaveBannerRequest）の境界条件は BannerStoreTest に置き、ここでは更新でも検証が働く代表1ケースのみ確かめる。
 */
class BannerUpdateTest extends TestCase
{
    use FakesImageUpload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function bannerWithImages(): Banner
    {
        Storage::disk('public')->put('banners/old-pc.jpg', 'pc');
        Storage::disk('public')->put('banners/old-sp.jpg', 'sp');

        return Banner::factory()->create([
            'pc_image_path' => 'banners/old-pc.jpg',
            'sp_image_path' => 'banners/old-sp.jpg',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            '_method' => 'put',
            'tag' => '更新後のラベル',
            'title' => '更新後の見出し',
            'subtitle' => '',
            'background' => BannerBackground::Teal->value,
            'product_id' => '',
            'link_url' => '',
            'sort_order' => '5',
            'is_active' => '0',
            ...$overrides,
        ];
    }

    #[Test]
    public function バナーを更新できる(): void
    {
        $admin = Admin::factory()->create();
        $banner = Banner::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.banners.index'))
            ->post(route('admin.banners.update', $banner), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.banners.index'));

        $banner->refresh();
        $this->assertSame('更新後のラベル', $banner->tag);
        $this->assertNull($banner->subtitle);
        $this->assertSame(BannerBackground::Teal, $banner->background);
        $this->assertSame(5, $banner->sort_order);
        $this->assertFalse($banner->is_active);
    }

    #[Test]
    public function 画像を差し替えると元の画像ファイルが削除される(): void
    {
        $admin = Admin::factory()->create();
        $banner = $this->bannerWithImages();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.update', $banner), $this->payload([
                'pc_image' => $this->fakeImage('new-pc.jpg'),
            ]))
            ->assertSessionHasNoErrors();

        $banner->refresh();
        $this->assertNotSame('banners/old-pc.jpg', $banner->pc_image_path);
        Storage::disk('public')->assertExists($banner->pc_image_path);
        Storage::disk('public')->assertMissing('banners/old-pc.jpg');
        $this->assertSame('banners/old-sp.jpg', $banner->sp_image_path);
        Storage::disk('public')->assertExists('banners/old-sp.jpg');
    }

    #[Test]
    public function 画像の削除を指定すると画像ファイルとパスが削除される(): void
    {
        $admin = Admin::factory()->create();
        $banner = $this->bannerWithImages();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.update', $banner), $this->payload(['remove_sp_image' => '1']))
            ->assertSessionHasNoErrors();

        $banner->refresh();
        $this->assertNull($banner->sp_image_path);
        Storage::disk('public')->assertMissing('banners/old-sp.jpg');
        $this->assertSame('banners/old-pc.jpg', $banner->pc_image_path);
        Storage::disk('public')->assertExists('banners/old-pc.jpg');
    }

    #[Test]
    public function 画像を指定しなければ既存の画像が残る(): void
    {
        $admin = Admin::factory()->create();
        $banner = $this->bannerWithImages();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.update', $banner), $this->payload([
                'remove_pc_image' => '0',
                'remove_sp_image' => '0',
            ]))
            ->assertSessionHasNoErrors();

        $banner->refresh();
        $this->assertSame('banners/old-pc.jpg', $banner->pc_image_path);
        $this->assertSame('banners/old-sp.jpg', $banner->sp_image_path);
        Storage::disk('public')->assertExists(['banners/old-pc.jpg', 'banners/old-sp.jpg']);
    }

    #[Test]
    public function 入力が不正なら更新されない(): void
    {
        $admin = Admin::factory()->create();
        $banner = Banner::factory()->create(['tag' => '元のラベル']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.update', $banner), $this->payload(['link_url' => '//example.com/']))
            ->assertSessionHasErrors('link_url');

        $this->assertSame('元のラベル', $banner->refresh()->tag);
    }

    #[Test]
    public function 未認証はバナーを更新できない(): void
    {
        $banner = Banner::factory()->create(['tag' => '元のラベル']);

        $this->post(route('admin.banners.update', $banner), $this->payload())
            ->assertRedirect(route('admin.login'));

        $this->assertSame('元のラベル', $banner->refresh()->tag);
    }
}
