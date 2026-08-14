<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvEncodingTest extends TestCase
{
    use RefreshDatabase;

    private const BOM = "\xEF\xBB\xBF";

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        Category::factory()->create(['name' => 'ロードバイク']);
    }

    private function utf8Content(): string
    {
        $header = '商品ID,商品名,カテゴリ,価格（税込）,SKU有無,枝番,在庫数,公開状態';
        $row = 'RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開';

        return $header."\r\n".$row."\r\n";
    }

    private function importContent(string $content): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.csv.import'),
            ['file' => UploadedFile::fake()->createWithContent('products.csv', $content)],
        );
    }

    #[Test]
    public function 文字コードが自動判定されて取り込める(): void
    {
        $this->importContent($this->utf8Content());

        $this->assertDatabaseHas('products', [
            'product_code' => 'RC7-105',
            'name' => 'ロードスター',
        ]);
    }

    #[Test]
    public function 日本語を含む値が文字化けせず取り込める(): void
    {
        $this->importContent($this->utf8Content());

        $product = Product::query()->where('product_code', 'RC7-105')->sole();

        $this->assertSame('ロードスター', $product->name);
        $this->assertSame('ロードバイク', $product->category->name);
    }

    #[Test]
    public function バイトオーダーマーク付きでも取り込める(): void
    {
        $this->importContent(self::BOM.$this->utf8Content());

        $this->assertDatabaseHas('products', [
            'product_code' => 'RC7-105',
            'name' => 'ロードスター',
        ]);
    }

    #[Test]
    public function 日本語を含む旧来の文字コードでも取り込める(): void
    {
        $sjis = mb_convert_encoding($this->utf8Content(), 'SJIS-win', 'UTF-8');

        $this->importContent((string) $sjis);

        $this->assertDatabaseHas('products', [
            'product_code' => 'RC7-105',
            'name' => 'ロードスター',
        ]);
    }

    #[Test]
    public function 改行が復帰改行でなくても取り込める(): void
    {
        $content = str_replace("\r\n", "\n", $this->utf8Content());

        $this->importContent($content);

        $this->assertDatabaseHas('products', ['product_code' => 'RC7-105']);
    }

    #[Test]
    public function 空行は読み飛ばされる(): void
    {
        $content = $this->utf8Content()."\r\n,,,,,,,\r\n";

        $this->importContent($content);

        $this->assertDatabaseCount('products', 1);
    }
}
