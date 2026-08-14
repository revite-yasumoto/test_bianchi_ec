<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->category = Category::factory()->create(['name' => 'ロードバイク']);
    }

    private function makeProduct(string $code, string $name, bool $hasSku = false): Product
    {
        return Product::factory()->create([
            'product_code' => $code,
            'name' => $name,
            'category_id' => $this->category->id,
            'price' => 398000,
            'has_sku' => $hasSku,
            'is_published' => true,
        ]);
    }

    private function addVariant(Product $product, ?string $branchCode, int $quantity): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'branch_code' => $branchCode,
            'size_name' => null,
            'color_name' => null,
        ]);

        Stock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    private function exportContent(): string
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.csv.export'));

        $response->assertOk();

        return $response->streamedContent();
    }

    #[Test]
    public function 商品が書き出される(): void
    {
        $product = $this->makeProduct('RC7-105', 'ロードスター');
        $this->addVariant($product, null, 12);

        $content = $this->exportContent();

        $this->assertStringContainsString('RC7-105', $content);
        $this->assertStringContainsString('ロードスター', $content);
        $this->assertStringContainsString('ロードバイク', $content);
    }

    #[Test]
    public function 先頭にバイトオーダーマークが付く(): void
    {
        $product = $this->makeProduct('RC7-105', 'ロードスター');
        $this->addVariant($product, null, 12);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $this->exportContent());
    }

    #[Test]
    public function 改行コードが復帰改行になる(): void
    {
        $product = $this->makeProduct('RC7-105', 'ロードスター');
        $this->addVariant($product, null, 12);

        $content = $this->exportContent();

        $this->assertStringContainsString("\r\n", $content);
        // LF 単独の改行が残っていないこと
        $this->assertSame(
            substr_count($content, "\r\n"),
            substr_count($content, "\n"),
        );
    }

    #[Test]
    public function ヘッダー行が出力される(): void
    {
        $product = $this->makeProduct('RC7-105', 'ロードスター');
        $this->addVariant($product, null, 12);

        $this->assertStringContainsString('商品ID', $this->exportContent());
    }

    #[Test]
    public function 規格あり商品はバリエーションごとの行に展開される(): void
    {
        $product = $this->makeProduct('JS-2026', 'チームジャージ', true);
        $this->addVariant($product, '11', 3);
        $this->addVariant($product, '12', 5);

        $content = $this->exportContent();
        $lines = array_filter(explode("\r\n", $content));

        // ヘッダー1行 + バリエーション2行
        $this->assertCount(3, $lines);
    }

    #[Test]
    public function 商品が無くてもヘッダーだけが出力される(): void
    {
        $content = $this->exportContent();

        $this->assertStringContainsString('商品ID', $content);
    }

    #[Test]
    public function テンプレートはヘッダーだけを含む(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.csv.template'));

        $response->assertOk();
        $lines = array_filter(explode("\r\n", $response->streamedContent()));

        $this->assertCount(1, $lines);
    }

    #[Test]
    public function 未認証は書き出せない(): void
    {
        $this->get(route('admin.products.csv.export'))
            ->assertRedirect(route('admin.login'));
    }
}
