<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCsvImportTest extends TestCase
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

    /**
     * @param  array<int, string>  $lines
     */
    private function csv(array $lines): UploadedFile
    {
        $header = '商品ID,商品名,カテゴリ,価格（税込）,SKU有無,枝番,在庫数,公開状態';
        $content = implode("\r\n", array_merge([$header], $lines))."\r\n";

        return UploadedFile::fake()->createWithContent('products.csv', $content);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function import(array $lines): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.csv.import'),
            ['file' => $this->csv($lines)],
        );
    }

    #[Test]
    public function 商品の取込画面が表示される(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.csv.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Product/Csv')
                ->has('columns', 8)
            );
    }

    #[Test]
    public function 規格なし商品を新規登録できる(): void
    {
        $this->import(['RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開']);

        $product = Product::query()->where('product_code', 'RC7-105')->sole();

        $this->assertSame('ロードスター', $product->name);
        $this->assertSame(398000, $product->price);
        $this->assertFalse($product->has_sku);
        $this->assertTrue($product->is_published);
        $this->assertCount(1, $product->variants);
        $this->assertSame(12, $product->variants->first()->stock->quantity);
    }

    #[Test]
    public function 既存商品を更新できる(): void
    {
        $product = Product::factory()->create([
            'product_code' => 'RC7-105',
            'name' => '旧商品名',
            'category_id' => $this->category->id,
            'price' => 100000,
        ]);

        $this->import(['RC7-105,ロードスター,ロードバイク,398000,なし,,5,公開']);

        $product->refresh();

        $this->assertSame('ロードスター', $product->name);
        $this->assertSame(398000, $product->price);
        $this->assertDatabaseCount('products', 1);
    }

    #[Test]
    public function 規格あり商品は同じ商品識別コードの複数行から取り込まれる(): void
    {
        $this->import([
            'JS-2026,チームジャージ,ロードバイク,12000,あり,11,3,公開',
            'JS-2026,チームジャージ,ロードバイク,12000,あり,12,5,公開',
        ]);

        $product = Product::query()->where('product_code', 'JS-2026')->sole();

        $this->assertTrue($product->has_sku);
        $this->assertCount(2, $product->variants);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'branch_code' => '11',
            'sku_code' => 'JS-2026-11',
        ]);
        $this->assertSame(8, (int) $product->stocks()->sum('quantity'));
    }

    #[Test]
    public function 既存の枝番に一致する行は在庫だけが更新される(): void
    {
        $this->import(['JS-2026,チームジャージ,ロードバイク,12000,あり,11,3,公開']);
        $this->import(['JS-2026,チームジャージ,ロードバイク,12000,あり,11,9,公開']);

        $product = Product::query()->where('product_code', 'JS-2026')->sole();

        $this->assertCount(1, $product->variants);
        $this->assertSame(9, $product->variants->first()->stock->quantity);
    }

    #[Test]
    public function 公開状態を省略すると非公開になる(): void
    {
        $this->import(['RC7-105,ロードスター,ロードバイク,398000,なし,,12,']);

        $this->assertFalse(
            Product::query()->where('product_code', 'RC7-105')->sole()->is_published,
        );
    }

    #[Test]
    public function 存在しないカテゴリ名はエラーになる(): void
    {
        $this->import(['RC7-105,ロードスター,存在しないカテゴリ,398000,なし,,12,公開']);

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function 規格ありで枝番が空の行はエラーになる(): void
    {
        $this->import(['JS-2026,チームジャージ,ロードバイク,12000,あり,,3,公開']);

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function 必須列が欠けている行はエラーになる(): void
    {
        $this->import([',ロードスター,ロードバイク,398000,なし,,12,公開']);

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function 一行でもエラーがあれば1件も取り込まれない(): void
    {
        $this->import([
            'RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開',
            'MT3-STD,トレイルヘッド,存在しないカテゴリ,198000,なし,,5,公開',
        ]);

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function インポート結果が行番号付きで返る(): void
    {
        $response = $this->import([
            'RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開',
            'MT3-STD,トレイルヘッド,存在しないカテゴリ,198000,なし,,5,公開',
        ]);

        $response->assertSessionHas('importResult', function (array $result): bool {
            // ヘッダーを1行目として数えるため、2件目のデータ行は3行目になる
            return $result['created'] === 0
                && count($result['errors']) === 1
                && $result['errors'][0]['line'] === 3;
        });
    }

    #[Test]
    public function 成功時のインポート結果に件数が入る(): void
    {
        $response = $this->import([
            'RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開',
        ]);

        $response->assertSessionHas('importResult', function (array $result): bool {
            return $result['created'] === 1
                && $result['updated'] === 0
                && $result['errors'] === [];
        });
    }

    #[Test]
    public function 未認証はインポートできない(): void
    {
        $this->post(route('admin.products.csv.import'), [
            'file' => $this->csv(['RC7-105,ロードスター,ロードバイク,398000,なし,,12,公開']),
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('products', 0);
    }
}
