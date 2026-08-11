<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = Admin::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * GD拡張が無い環境でも `image` / `dimensions` ルールを通せるよう、1x1のPNG実バイナリを使う。
     */
    private function fakeImage(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, (string) $png);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_code' => 'RC7-105',
            'name' => 'ロードスター RC7',
            'category_id' => $this->category->id,
            'price' => 398000,
            'description' => null,
            'is_published' => true,
            'has_sku' => false,
            'specs' => [],
            'variants' => [
                ['size_name' => null, 'color_name' => null, 'branch_code' => null, 'is_available' => true, 'quantity' => 1],
            ],
        ], $overrides);
    }

    private function makeProductWithImages(int $count): Product
    {
        $product = Product::factory()->create([
            'product_code' => 'RC7-105',
            'category_id' => $this->category->id,
        ]);

        for ($index = 0; $index < $count; $index++) {
            $path = "products/{$product->id}/image{$index}.png";
            Storage::disk('public')->put($path, 'dummy');

            ProductImage::factory()->create([
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $index,
            ]);
        }

        return $product;
    }

    #[Test]
    public function 登録した画像は先頭が表示順0で保存される(): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            $this->payload([
                'images' => [
                    $this->fakeImage('main.png'),
                    $this->fakeImage('sub.png'),
                ],
            ]),
        );

        $product = Product::query()->where('product_code', 'RC7-105')->sole();
        $images = $product->images;

        $this->assertCount(2, $images);
        $this->assertSame(0, $images[0]->sort_order);
        $this->assertSame(1, $images[1]->sort_order);
    }

    #[Test]
    public function 登録した画像の実ファイルがストレージに保存される(): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            $this->payload(['images' => [$this->fakeImage('main.png')]]),
        );

        $product = Product::query()->where('product_code', 'RC7-105')->sole();

        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    #[Test]
    public function 画像を削除すると表示順が詰められる(): void
    {
        $product = $this->makeProductWithImages(3);
        $middle = $product->images[1];

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload(['deleted_image_ids' => [$middle->id]]),
        );

        $product->refresh();
        $images = $product->images;

        $this->assertCount(2, $images);
        $this->assertSame([0, 1], $images->pluck('sort_order')->all());
        $this->assertDatabaseMissing('product_images', ['id' => $middle->id]);
    }

    #[Test]
    public function 削除した画像の実ファイルはストレージから消える(): void
    {
        $product = $this->makeProductWithImages(2);
        $target = $product->images->first();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload(['deleted_image_ids' => [$target->id]]),
        );

        Storage::disk('public')->assertMissing($target->path);
    }

    #[Test]
    public function 既存画像を残したまま追加すると後ろに並ぶ(): void
    {
        $product = $this->makeProductWithImages(2);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload(['images' => [$this->fakeImage('added.png')]]),
        );

        $product->refresh();

        $this->assertCount(3, $product->images);
        $this->assertSame([0, 1, 2], $product->images->pluck('sort_order')->all());
        $this->assertStringContainsString(
            'products/'.$product->id,
            $product->images->last()->path,
        );
    }

    #[Test]
    public function 既存と新規の合計が上限を超えると登録できない(): void
    {
        $product = $this->makeProductWithImages(9);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            $this->payload([
                'images' => [
                    $this->fakeImage('a.png'),
                    $this->fakeImage('b.png'),
                ],
            ]),
        )->assertSessionHasErrors('images');

        $this->assertCount(9, $product->refresh()->images);
    }
}
