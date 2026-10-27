<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Banner;

use App\Models\Admin;
use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 画像を持つバナーは Factory と偽のストレージで用意する。画像を保存して作成する経路は BannerStoreTest が検証している。
 */
class BannerDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    #[Test]
    public function バナーを削除すると画像ファイルも削除される(): void
    {
        $admin = Admin::factory()->create();
        Storage::disk('public')->put('banners/pc.jpg', 'pc');
        $banner = Banner::factory()->create(['pc_image_path' => 'banners/pc.jpg']);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.banners.index'))
            ->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('banners/pc.jpg');
    }

    #[Test]
    public function 未認証はバナーを削除できない(): void
    {
        $banner = Banner::factory()->create();

        $this->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('banners', ['id' => $banner->id]);
    }
}
