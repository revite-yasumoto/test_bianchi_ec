<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Contact;

use App\Enums\ContactStatus;
use App\Models\Admin;
use App\Models\Contact;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 問い合わせは Factory で用意する。送信の経路（`POST /contact`）は
 * `Tests\Feature\Front\Contact\ContactStoreTest`、対応状況（`status`・`admin_note`・
 * `handled_admin_id`・`handled_at`）の更新経路は
 * `Tests\Feature\Admin\Contact\ContactUpdateTest` が検証している。
 * 受信日時だけは、過去日時にする入口が無い（送信時は常に現在時刻）ため直接指定する。
 */
class ContactIndexTest extends TestCase
{
    use RefreshDatabase;

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
    private function index(array $query = []): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.index', $query));
    }

    #[Test]
    public function お問い合わせ一覧が表示される(): void
    {
        $this->makeContact([
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'status' => ContactStatus::Unhandled,
        ], '2026-08-18 10:30:00');

        $this->index()
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Contact/Index')
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '架空 太郎')
                ->where('contacts.data.0.email', 'taro@example.test')
                ->where('contacts.data.0.received_at', '2026.08.18 10:30')
                ->where('contacts.data.0.status_label', '未対応')
                ->where('totalCount', 1)
                ->has('statusOptions', 3)
            );
    }

    #[Test]
    public function 未認証ではお問い合わせ一覧を開けない(): void
    {
        $this->get(route('admin.contacts.index'))
            ->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function 既定では商品を持たないお問い合わせだけが表示される(): void
    {
        $this->makeContact(['name' => '通常の問い合わせ']);
        $this->makeContact([
            'name' => '商品からの問い合わせ',
            'product_id' => Product::factory()->create()->id,
        ]);

        $this->index()
            ->assertInertia(fn ($page) => $page
                ->where('filters.tab', 'general')
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '通常の問い合わせ')
            );
    }

    #[Test]
    public function 商品からのお問い合わせタブでは商品を持つものだけが表示される(): void
    {
        $this->makeContact(['name' => '通常の問い合わせ']);
        $this->makeContact([
            'name' => '商品からの問い合わせ',
            'product_id' => Product::factory()->create()->id,
        ]);

        $this->index(['tab' => 'product'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '商品からの問い合わせ')
            );
    }

    #[Test]
    public function 商品からのお問い合わせタブでは対象商品の商品識別コードが渡る(): void
    {
        $product = Product::factory()->create(['product_code' => 'PC-0001']);
        $this->makeContact([
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
            'product_code' => 'PC-0001',
        ]);

        $this->index(['tab' => 'product'])
            ->assertInertia(fn ($page) => $page
                ->where('contacts.data.0.product_name', '架空ジャージ 2026')
                ->where('contacts.data.0.product_code', 'PC-0001')
            );
    }

    #[Test]
    public function 商品名を手入力しただけの問い合わせは通常のタブに入る(): void
    {
        $this->makeContact([
            'name' => '手入力の問い合わせ',
            'product_id' => null,
            'product_name' => '架空ジャージ 2026',
        ]);

        $this->index()
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));

        $this->index(['tab' => 'product'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 0));
    }

    #[Test]
    public function 不正なタブの値は通常のお問い合わせに丸められる(): void
    {
        $this->index(['tab' => 'unknown'])
            ->assertInertia(fn ($page) => $page->where('filters.tab', 'general'));
    }

    #[Test]
    public function 表示中のタブの総件数は絞り込みの影響を受けない(): void
    {
        $this->makeContact(['name' => '通常A']);
        $this->makeContact(['name' => '通常B']);
        $this->makeContact([
            'name' => '商品A',
            'product_id' => Product::factory()->create()->id,
        ]);

        $this->index(['q' => '通常A'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('totalCount', 2)
            );

        $this->index(['tab' => 'product', 'q' => '該当しない語'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 0)
                ->where('totalCount', 1)
            );
    }

    #[Test]
    public function ステータスで絞り込める(): void
    {
        $this->makeContact(['name' => '未対応の問い合わせ', 'status' => ContactStatus::Unhandled]);
        $this->makeContact(['name' => '対応済みの問い合わせ', 'status' => ContactStatus::Handled]);

        $this->index(['status' => ContactStatus::Handled->value])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '対応済みの問い合わせ')
            );
    }

    #[Test]
    public function 不正なステータスの値はすべてに丸められる(): void
    {
        $this->makeContact(['status' => ContactStatus::Unhandled]);
        $this->makeContact(['status' => ContactStatus::Handled]);

        $this->index(['status' => 'unknown'])
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', 'all')
                ->has('contacts.data', 2)
            );
    }

    #[Test]
    public function キーワードで氏名とメールアドレスと対象商品と本文を検索できる(): void
    {
        $this->makeContact(['name' => '架空 太郎', 'email' => 'a@example.test', 'product_name' => null, 'body' => '本文のみ一致しない内容']);
        $this->makeContact(['name' => '別名', 'email' => 'hanako@example.test', 'product_name' => null, 'body' => '内容']);
        $this->makeContact(['name' => '別名', 'email' => 'b@example.test', 'product_name' => '架空ジャージ 2026', 'body' => '内容']);
        $this->makeContact(['name' => '別名', 'email' => 'c@example.test', 'product_name' => null, 'body' => 'サイズ選びについて相談です']);

        $this->index(['q' => '架空 太郎'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
        $this->index(['q' => 'hanako'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
        $this->index(['q' => 'ジャージ'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
        $this->index(['q' => 'サイズ選び'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
    }

    #[Test]
    public function 検索語のワイルドカードは部分一致として扱われない(): void
    {
        $this->makeContact(['name' => '架空 太郎']);
        $this->makeContact(['name' => '100%の力']);

        $this->index(['q' => '%'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '100%の力')
            );

        $this->index(['q' => '_'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 0));
    }

    #[Test]
    public function キーワードの条件はステータスの条件を飲み込まない(): void
    {
        $this->makeContact(['name' => '架空 太郎', 'status' => ContactStatus::Unhandled]);
        $this->makeContact(['name' => '架空 太郎', 'status' => ContactStatus::Handled]);

        $this->index(['q' => '架空', 'status' => ContactStatus::Handled->value])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
    }

    #[Test]
    public function 受信日の開始と終了で絞り込め当日分が両端とも含まれる(): void
    {
        $this->makeContact(['name' => '期間前'], '2026-08-16 23:59:59');
        $this->makeContact(['name' => '開始日'], '2026-08-17 00:00:00');
        $this->makeContact(['name' => '終了日'], '2026-08-18 23:59:59');
        $this->makeContact(['name' => '期間後'], '2026-08-19 00:00:00');

        $this->index(['from' => '2026-08-17', 'to' => '2026-08-18'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 2));
    }

    #[Test]
    public function 受信日は開始だけでも終了だけでも絞り込める(): void
    {
        $this->makeContact(['name' => '古い'], '2026-08-10 10:00:00');
        $this->makeContact(['name' => '新しい'], '2026-08-20 10:00:00');

        $this->index(['from' => '2026-08-15'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '新しい')
            );

        $this->index(['to' => '2026-08-15'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '古い')
            );
    }

    #[Test]
    public function 日付として解釈できない受信日は条件なしとして扱われる(): void
    {
        $this->makeContact([], '2026-08-18 10:00:00');

        $this->index(['from' => 'not-a-date', 'to' => '2026-99-99'])
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
    }

    #[Test]
    public function 開始日が終了日より後なら結果は空になる(): void
    {
        $this->makeContact([], '2026-08-18 10:00:00');

        $this->index(['from' => '2026-08-20', 'to' => '2026-08-10'])
            ->assertInertia(fn ($page) => $page->has('contacts.data', 0));
    }

    #[Test]
    public function 文字列以外のクエリは既定値に丸められる(): void
    {
        $this->makeContact();

        $this->actingAs($this->admin, 'admin')
            ->get('/admin/contacts?tab[]=product&status[]=handled&q[]=x&from[]=2026-08-01&to[]=2026-08-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.tab', 'general')
                ->where('filters.status', 'all')
                ->where('filters.q', null)
                ->where('filters.from', null)
                ->where('filters.to', null)
                ->has('contacts.data', 1)
            );
    }

    #[Test]
    public function 検索語が零でも絞り込みが効く(): void
    {
        $this->makeContact(['name' => '架空 太郎', 'body' => '在庫は0個ですか']);
        // Factory のメールアドレスは数字を含むことがあり、検索語 "0" に偶然ヒットするため固定する
        $this->makeContact([
            'name' => '架空 花子',
            'body' => '納期を教えてください',
            'email' => 'hanako@example.test',
        ]);

        $this->index(['q' => '0'])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '架空 太郎')
            );
    }

    #[Test]
    public function ページ送りでも絞り込みが維持される(): void
    {
        Contact::factory()->count(51)->create(['name' => '架空 太郎']);
        Contact::factory()->count(3)->create(['name' => '別の送信者']);

        $this->index(['q' => '架空 太郎', 'page' => 2])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '架空 太郎')
                ->where('contacts.total', 51)
                ->where('filters.q', '架空 太郎')
            );
    }

    #[Test]
    public function タブと絞り込みを組み合わせられる(): void
    {
        $product = Product::factory()->create();

        $this->makeContact(['name' => '通常 未対応', 'status' => ContactStatus::Unhandled]);
        $this->makeContact(['name' => '商品 未対応', 'product_id' => $product->id, 'status' => ContactStatus::Unhandled]);
        $this->makeContact(['name' => '商品 対応済み', 'product_id' => $product->id, 'status' => ContactStatus::Handled]);

        $this->index(['tab' => 'product', 'status' => ContactStatus::Unhandled->value])
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', '商品 未対応')
            );
    }

    #[Test]
    public function 一覧は受信日時の降順で並ぶ(): void
    {
        $this->makeContact(['name' => '古い'], '2026-08-10 10:00:00');
        $this->makeContact(['name' => '新しい'], '2026-08-20 10:00:00');
        $this->makeContact(['name' => '中間'], '2026-08-15 10:00:00');

        $this->index()
            ->assertInertia(fn ($page) => $page
                ->where('contacts.data.0.name', '新しい')
                ->where('contacts.data.1.name', '中間')
                ->where('contacts.data.2.name', '古い')
            );
    }

    #[Test]
    public function 五十件を超えるとページネーションされる(): void
    {
        Contact::factory()->count(51)->create();

        $this->index()
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 50)
                ->where('contacts.total', 51)
            );
    }

    #[Test]
    public function 該当がないときは空の一覧になる(): void
    {
        $this->index()
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('contacts.data', 0));
    }

    #[Test]
    public function 本文は六十文字で切られ続きがあるときだけ記号が付く(): void
    {
        $this->makeContact(['name' => '長い本文', 'body' => str_repeat('あ', 61)]);
        $this->makeContact(['name' => '短い本文', 'body' => str_repeat('い', 60)]);

        $this->index(['q' => '長い本文'])
            ->assertInertia(fn ($page) => $page
                ->where('contacts.data.0.body_excerpt', str_repeat('あ', 60).'…')
            );

        $this->index(['q' => '短い本文'])
            ->assertInertia(fn ($page) => $page
                ->where('contacts.data.0.body_excerpt', str_repeat('い', 60))
            );
    }
}
