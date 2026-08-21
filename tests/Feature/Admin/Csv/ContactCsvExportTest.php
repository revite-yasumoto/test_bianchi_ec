<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Csv;

use App\Enums\ContactStatus;
use App\Models\Admin;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 問い合わせは Factory で用意する。送信の経路（`POST /contact`）は
 * `Tests\Feature\Front\Contact\ContactStoreTest`、対応状況（`status`・`admin_note`・
 * `handled_admin_id`・`handled_at`）の更新経路は
 * `Tests\Feature\Admin\Contact\ContactUpdateTest` が検証している。
 * 受信日時だけは、過去日時にする入口が無い（送信時は常に現在時刻）ため直接指定する。
 */
class ContactCsvExportTest extends TestCase
{
    use RefreshDatabase;

    /** `ContactCsvExporter::header()` の列順に対応する添字 */
    private const COL_NUMBER = 0;

    private const COL_RECEIVED_AT = 1;

    private const COL_KIND = 2;

    private const COL_NAME = 3;

    private const COL_EMAIL = 4;

    private const COL_MEMBER_CODE = 5;

    private const COL_PRODUCT_NAME = 6;

    private const COL_PRODUCT_CODE = 7;

    private const COL_BODY = 8;

    private const COL_STATUS = 9;

    private const COL_ADMIN_NOTE = 10;

    private const COL_HANDLER = 11;

    private const COL_HANDLED_AT = 12;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeContact(array $attributes = [], ?string $createdAt = null): Contact
    {
        return Contact::factory()->create(
            $createdAt === null ? $attributes : [...$attributes, 'created_at' => $createdAt],
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function exportContent(array $query = []): string
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.csv.export', $query));

        $response->assertOk();

        return $response->streamedContent();
    }

    /**
     * 本文に改行を含む行も1レコードとして読めるよう、CSVとして解析して行の配列を返す。
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<int, string>>
     */
    private function exportRows(array $query = []): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, ltrim($this->exportContent($query), "\xEF\xBB\xBF"));
        rewind($stream);

        $rows = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    #[Test]
    public function 全ての列が定義どおりの順序で書き出される(): void
    {
        $user = User::factory()->create(['member_code' => 'M-100238']);
        $product = Product::factory()->create(['product_code' => 'PC-0001']);
        $handler = Admin::factory()->create(['name' => '架空 管理者']);

        $this->makeContact([
            'contact_number' => 'INQ-2608-0121',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'product_name' => '架空ジャージ 2026',
            'product_code' => 'PC-0001',
            'body' => 'サイズ選びについて相談です。',
            'status' => ContactStatus::Handled,
            'admin_note' => '在庫を案内済み',
            'handled_admin_id' => $handler->id,
            'handled_at' => '2026-08-18 11:20:00',
        ], '2026-08-18 10:30:00');

        $rows = $this->exportRows(['tab' => 'product']);

        $this->assertSame([
            '問い合わせ番号', '受信日時', '種別', 'お名前', 'メールアドレス', '会員ID', '対象商品',
            '商品ID', 'お問い合わせ内容', 'ステータス', '対応メモ', '対応者', '対応日時',
        ], $rows[0]);

        $this->assertSame('INQ-2608-0121', $rows[1][self::COL_NUMBER]);
        $this->assertSame('2026-08-18 10:30:00', $rows[1][self::COL_RECEIVED_AT]);
        $this->assertSame('商品', $rows[1][self::COL_KIND]);
        $this->assertSame('架空 太郎', $rows[1][self::COL_NAME]);
        $this->assertSame('taro@example.test', $rows[1][self::COL_EMAIL]);
        $this->assertSame('M-100238', $rows[1][self::COL_MEMBER_CODE]);
        $this->assertSame('架空ジャージ 2026', $rows[1][self::COL_PRODUCT_NAME]);
        $this->assertSame('PC-0001', $rows[1][self::COL_PRODUCT_CODE]);
        $this->assertSame('サイズ選びについて相談です。', $rows[1][self::COL_BODY]);
        $this->assertSame('対応済み', $rows[1][self::COL_STATUS]);
        $this->assertSame('在庫を案内済み', $rows[1][self::COL_ADMIN_NOTE]);
        $this->assertSame('架空 管理者', $rows[1][self::COL_HANDLER]);
        $this->assertSame('2026-08-18 11:20:00', $rows[1][self::COL_HANDLED_AT]);
    }

    #[Test]
    public function 未認証では書き出せない(): void
    {
        $this->makeContact();

        $this->get(route('admin.contacts.csv.export'))
            ->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function 種別は対象商品の有無で出し分けられる(): void
    {
        $this->makeContact(['name' => '通常の問い合わせ']);

        $rows = $this->exportRows();

        $this->assertSame('通常', $rows[1][self::COL_KIND]);
    }

    #[Test]
    public function 表示中のタブと絞り込み条件が反映される(): void
    {
        $product = Product::factory()->create();

        $this->makeContact(['name' => '通常の問い合わせ']);
        $this->makeContact(['name' => '商品の未対応', 'product_id' => $product->id, 'status' => ContactStatus::Unhandled]);
        $this->makeContact(['name' => '商品の対応済み', 'product_id' => $product->id, 'status' => ContactStatus::Handled]);

        $rows = $this->exportRows([
            'tab' => 'product',
            'status' => ContactStatus::Unhandled->value,
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame('商品の未対応', $rows[1][self::COL_NAME]);
    }

    #[Test]
    public function 未ログインからの送信では会員の識別子列が空になる(): void
    {
        $this->makeContact(['user_id' => null, 'name' => '架空 太郎']);

        $rows = $this->exportRows();

        $this->assertSame('', $rows[1][self::COL_MEMBER_CODE]);
        $this->assertSame('架空 太郎', $rows[1][self::COL_NAME]);
    }

    #[Test]
    public function 商品が削除された行でも商品の識別子列が残る(): void
    {
        $product = Product::factory()->create(['product_code' => 'PC-0002']);
        $this->makeContact([
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
            'product_code' => 'PC-0002',
        ]);

        $product->delete();

        $rows = $this->exportRows();

        $this->assertSame('架空ジャージ 2026', $rows[1][self::COL_PRODUCT_NAME]);
        $this->assertSame('PC-0002', $rows[1][self::COL_PRODUCT_CODE]);
    }

    #[Test]
    public function 手入力の問い合わせは商品の識別子列が空になる(): void
    {
        $this->makeContact([
            'product_id' => null,
            'product_name' => '手入力の商品名',
            'product_code' => null,
        ]);

        $rows = $this->exportRows();

        $this->assertSame('手入力の商品名', $rows[1][self::COL_PRODUCT_NAME]);
        $this->assertSame('', $rows[1][self::COL_PRODUCT_CODE]);
    }

    #[Test]
    public function 未対応と対応中の行は対応日時列が空になる(): void
    {
        $this->makeContact([
            'name' => '対応中の問い合わせ',
            'status' => ContactStatus::InProgress,
            'handled_at' => null,
        ], '2026-08-18 10:30:00');
        $this->makeContact([
            'name' => '未対応の問い合わせ',
            'status' => ContactStatus::Unhandled,
            'handled_at' => null,
        ], '2026-08-17 10:30:00');

        $rows = $this->exportRows();

        $this->assertSame('対応中', $rows[1][self::COL_STATUS]);
        $this->assertSame('', $rows[1][self::COL_HANDLED_AT]);
        $this->assertSame('未対応', $rows[2][self::COL_STATUS]);
        $this->assertSame('', $rows[2][self::COL_HANDLED_AT]);
    }

    #[Test]
    public function 本文の改行はセル内に保たれる(): void
    {
        $this->makeContact(['body' => "1行目です。\n2行目です。"]);

        $rows = $this->exportRows();

        $this->assertCount(2, $rows);
        $this->assertSame("1行目です。\n2行目です。", $rows[1][self::COL_BODY]);
    }

    #[Test]
    public function 先頭にバイトオーダーマークが付き改行は復帰改行になる(): void
    {
        $this->makeContact();

        $content = $this->exportContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString("\r\n", $content);
    }

    #[Test]
    public function 受信日時の降順で書き出される(): void
    {
        $this->makeContact(['name' => '古い問い合わせ'], '2026-08-10 10:00:00');
        $this->makeContact(['name' => '新しい問い合わせ'], '2026-08-20 10:00:00');

        $rows = $this->exportRows();

        $this->assertSame('新しい問い合わせ', $rows[1][self::COL_NAME]);
        $this->assertSame('古い問い合わせ', $rows[2][self::COL_NAME]);
    }

    #[Test]
    public function 会員と対応者を引くクエリが行数に比例しない(): void
    {
        $handler = Admin::factory()->create();
        $makeRow = function (int $index) use ($handler): void {
            $this->makeContact([
                'user_id' => User::factory()->create()->id,
                'handled_admin_id' => $handler->id,
                'name' => '問い合わせ'.$index,
            ]);
        };

        $makeRow(1);
        $oneRow = $this->countExportQueries();

        $makeRow(2);
        $makeRow(3);
        $threeRows = $this->countExportQueries();

        $this->assertSame($oneRow, $threeRows);
    }

    private function countExportQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->exportContent();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
