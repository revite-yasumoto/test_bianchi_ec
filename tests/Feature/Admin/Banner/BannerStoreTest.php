<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Banner;

use App\Enums\BannerBackground;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesImageUpload;
use Tests\TestCase;

class BannerStoreTest extends TestCase
{
    use FakesImageUpload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'tag' => '2026 ROAD COLLECTION',
            'title' => "軽さは、\n遠くまで行くための道具だ。",
            'subtitle' => '新型フレーム登場',
            'background' => BannerBackground::Blue->value,
            'product_id' => '',
            'link_url' => '/products',
            'sort_order' => '3',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    #[Test]
    public function 画像付きでバナーを作成できる(): void
    {
        $admin = Admin::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.banners.index'))
            ->post(route('admin.banners.store'), $this->payload([
                'product_id' => (string) $product->id,
                'pc_image' => $this->fakeImage('pc.jpg'),
                'sp_image' => $this->fakeImage('sp.png'),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::query()->sole();
        $this->assertSame('2026 ROAD COLLECTION', $banner->tag);
        $this->assertSame(BannerBackground::Blue, $banner->background);
        $this->assertSame($product->id, $banner->product_id);
        $this->assertSame(3, $banner->sort_order);
        $this->assertTrue($banner->is_active);
        $this->assertNotNull($banner->pc_image_path);
        $this->assertNotNull($banner->sp_image_path);
        Storage::disk('public')->assertExists([$banner->pc_image_path, $banner->sp_image_path]);
    }

    #[Test]
    public function 画像なしでバナーを作成できる(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.store'), $this->payload(['is_active' => '0']))
            ->assertSessionHasNoErrors();

        $banner = Banner::query()->sole();
        $this->assertNull($banner->pc_image_path);
        $this->assertNull($banner->sp_image_path);
        $this->assertFalse($banner->is_active);
    }

    #[Test]
    public function 必須項目が未入力では作成できない(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.store'), $this->payload(['tag' => '', 'title' => '', 'sort_order' => '']))
            ->assertSessionHasErrors(['tag', 'title', 'sort_order']);

        $this->assertDatabaseCount('banners', 0);
    }

    #[Test]
    public function 選択肢にない背景は指定できない(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.store'), $this->payload(['background' => 'url(https://example.com/x.png)']))
            ->assertSessionHasErrors('background');

        $this->assertDatabaseCount('banners', 0);
    }

    #[Test]
    public function サイト外へのリンク先は指定できない(): void
    {
        $admin = Admin::factory()->create();

        foreach (['https://example.com/', '//example.com/', '/\\example.com/', "/\t/example.com/", "/\n/example.com/", 'javascript:alert(1)'] as $linkUrl) {
            $this->actingAs($admin, 'admin')
                ->post(route('admin.banners.store'), $this->payload(['link_url' => $linkUrl]))
                ->assertSessionHasErrors('link_url');
        }

        $this->assertDatabaseCount('banners', 0);
    }

    #[Test]
    public function 許可されていない形式の画像は登録できない(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.banners.store'), $this->payload([
                'pc_image' => UploadedFile::fake()->create('pc.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('pc_image');

        $this->assertDatabaseCount('banners', 0);
    }

    #[Test]
    public function 表示順が範囲外では作成できない(): void
    {
        $admin = Admin::factory()->create();

        foreach (['-1', '10000', '1.5'] as $sortOrder) {
            $this->actingAs($admin, 'admin')
                ->post(route('admin.banners.store'), $this->payload(['sort_order' => $sortOrder]))
                ->assertSessionHasErrors('sort_order');
        }

        $this->assertDatabaseCount('banners', 0);
    }

    #[Test]
    public function 未認証はバナーを作成できない(): void
    {
        $this->post(route('admin.banners.store'), $this->payload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('banners', 0);
    }
}
