<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Product;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->category = Category::factory()->create();
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
     */
    private function postWith(array $overrides): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $this->payload($overrides));
    }

    #[Test]
    public function 商品識別コードが重複すると登録できない(): void
    {
        Product::factory()->create([
            'product_code' => 'RC7-105',
            'category_id' => $this->category->id,
        ]);

        $this->postWith([])->assertSessionHasErrors('product_code');
    }

    #[Test]
    public function 商品識別コードに記号を含むと登録できない(): void
    {
        $this->postWith(['product_code' => 'RC7_105@'])
            ->assertSessionHasErrors('product_code');
    }

    #[Test]
    public function 商品名が未入力では登録できない(): void
    {
        $this->postWith(['name' => ''])->assertSessionHasErrors('name');
    }

    #[Test]
    public function 存在しないカテゴリでは登録できない(): void
    {
        $this->postWith(['category_id' => 999])
            ->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function 価格が上限を超えると登録できない(): void
    {
        $this->postWith(['price' => 10000000])->assertSessionHasErrors('price');
    }

    #[Test]
    public function 価格が負の数では登録できない(): void
    {
        $this->postWith(['price' => -1])->assertSessionHasErrors('price');
    }

    #[Test]
    public function 規格ありでサイズが未入力では登録できない(): void
    {
        $this->postWith([
            'has_sku' => true,
            'variants' => [
                ['size_name' => null, 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('variants.0.size_name');
    }

    #[Test]
    public function 枝番が商品内で重複すると登録できない(): void
    {
        $this->postWith([
            'has_sku' => true,
            'variants' => [
                ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 1],
                ['size_name' => 'L', 'color_name' => 'ネイビー', 'branch_code' => '11', 'is_available' => true, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('variants.1.branch_code');
    }

    #[Test]
    public function 取扱ありで枝番が未入力では登録できない(): void
    {
        $this->postWith([
            'has_sku' => true,
            'variants' => [
                ['size_name' => 'M', 'color_name' => 'ネイビー', 'branch_code' => '', 'is_available' => true, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('variants.0.branch_code');
    }

    #[Test]
    public function バリエーションが空では登録できない(): void
    {
        $this->postWith(['variants' => []])->assertSessionHasErrors('variants');
    }

    #[Test]
    public function 商品画像が11枚を超えると登録できない(): void
    {
        Storage::fake('public');

        $images = [];

        for ($i = 0; $i < 11; $i++) {
            $images[] = $this->fakeImage("photo{$i}.png");
        }

        $this->postWith(['images' => $images])->assertSessionHasErrors('images');
    }

    #[Test]
    public function 許可されていない拡張子の画像は登録できない(): void
    {
        Storage::fake('public');

        $this->postWith(['images' => [UploadedFile::fake()->create('doc.pdf', 100)]])
            ->assertSessionHasErrors('images.0');
    }

    #[Test]
    public function 一枚ごとの画像エラーは日本語で返る(): void
    {
        Storage::fake('public');

        // 画面はこのキーのエラーを拾って表示するため、既定の英語メッセージのままにしない
        $this->postWith(['images' => [UploadedFile::fake()->create('doc.pdf', 100)]])
            ->assertSessionHasErrors([
                'images.0' => '商品画像は画像ファイルを選択してください。',
            ]);
    }

    #[Test]
    public function スペックの項目名が未入力では登録できない(): void
    {
        $this->postWith(['specs' => [['label' => '', 'value' => 'カーボン']]])
            ->assertSessionHasErrors('specs.0.label');
    }
}
