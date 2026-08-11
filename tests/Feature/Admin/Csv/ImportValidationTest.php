<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportValidationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        Category::factory()->create(['name' => 'ロードバイク']);
    }

    private function importFile(UploadedFile $file): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.csv.import'),
            ['file' => $file],
        );
    }

    private function validCsv(string $name = 'products.csv'): UploadedFile
    {
        $header = '商品ID,商品名,カテゴリ,価格（税込）,SKU有無,枝番,在庫数,公開状態';
        $row = 'RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開';

        return UploadedFile::fake()->createWithContent($name, $header."\r\n".$row."\r\n");
    }

    #[Test]
    public function 拡張子がテキストのファイルも取り込める(): void
    {
        $this->importFile($this->validCsv('products.txt'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 1);
    }

    #[Test]
    public function ファイルが未選択ならエラーになる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.csv.import'), [])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function 許可されていない拡張子は拒否される(): void
    {
        $this->importFile(UploadedFile::fake()->create('products.pdf', 10))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function 上限を超えるサイズのファイルは拒否される(): void
    {
        $file = UploadedFile::fake()->create('products.csv', 10241, 'text/csv');

        $this->importFile($file)->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function ヘッダーだけのファイルは何も取り込まない(): void
    {
        $header = '商品ID,商品名,カテゴリ,価格（税込）,SKU有無,枝番,在庫数,公開状態';
        $file = UploadedFile::fake()->createWithContent('products.csv', $header."\r\n");

        $this->importFile($file)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 0);
    }
}
