<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Contact;

use App\Models\Admin;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 問い合わせは Factory で用意する。送信の経路（`POST /contact`）は
 * `Tests\Feature\Front\Contact\ContactStoreTest` が検証している。
 */
class ContactShowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    #[Test]
    public function お問い合わせ詳細が表示される(): void
    {
        $contact = Contact::factory()->create([
            'contact_number' => 'INQ-2608-0121',
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'body' => "サイズ選びについて相談です。\n二行目の内容です。",
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Contact/Show')
                ->where('contact.name', '架空 太郎')
                ->where('contact.email', 'taro@example.test')
                ->where('contact.body', "サイズ選びについて相談です。\n二行目の内容です。")
                ->where('contact.status_label', '未対応')
                ->where('contact.contact_number', 'INQ-2608-0121')
                ->has('statusOptions', 3)
            );
    }

    #[Test]
    public function 未認証ではお問い合わせ詳細を開けない(): void
    {
        $contact = Contact::factory()->create();

        $this->get(route('admin.contacts.show', [$contact->id]))
            ->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function ログイン中に送信された問い合わせは会員コードが渡る(): void
    {
        $user = User::factory()->create(['member_code' => 'M-100238']);
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertInertia(fn ($page) => $page->where('contact.member_code', 'M-100238'));
    }

    #[Test]
    public function 未ログインから送信された問い合わせは会員コードが空になる(): void
    {
        $contact = Contact::factory()->create(['user_id' => null]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertInertia(fn ($page) => $page->where('contact.member_code', null));
    }

    #[Test]
    public function 商品からの問い合わせは商品の識別子と商品名が渡る(): void
    {
        $product = Product::factory()->create(['name' => '架空ジャージ 2026']);
        $contact = Contact::factory()->create([
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertInertia(fn ($page) => $page
                ->where('contact.product.id', $product->id)
                ->where('contact.product.name', '架空ジャージ 2026')
            );
    }

    #[Test]
    public function 商品が削除された問い合わせでも保存された商品名が残る(): void
    {
        $product = Product::factory()->create(['name' => '架空ジャージ 2026']);
        $contact = Contact::factory()->create([
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
        ]);

        $product->delete();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('contact.product', null)
                ->where('contact.product_name', '架空ジャージ 2026')
            );
    }

    #[Test]
    public function 商品からの問い合わせは商品識別コードが渡る(): void
    {
        $product = Product::factory()->create(['product_code' => 'PC-0001']);
        $contact = Contact::factory()->create([
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
            'product_code' => 'PC-0001',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertInertia(fn ($page) => $page->where('contact.product_code', 'PC-0001'));
    }

    #[Test]
    public function 商品が削除された問い合わせは商品識別コードが残る(): void
    {
        $product = Product::factory()->create(['product_code' => 'PC-0002']);
        $contact = Contact::factory()->create([
            'product_id' => $product->id,
            'product_name' => '架空ジャージ 2026',
            'product_code' => 'PC-0002',
        ]);

        $product->delete();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertInertia(fn ($page) => $page
                ->where('contact.product', null)
                ->where('contact.product_code', 'PC-0002')
            );
    }

    #[Test]
    public function 手入力の問い合わせは商品識別コードが空になる(): void
    {
        $contact = Contact::factory()->create([
            'product_id' => null,
            'product_name' => '手入力の商品名',
            'product_code' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertInertia(fn ($page) => $page
                ->where('contact.product', null)
                ->where('contact.product_name', '手入力の商品名')
                ->where('contact.product_code', null)
            );
    }

    #[Test]
    public function 会員が削除された問い合わせでも送信内容が表示される(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'name' => '架空 太郎',
        ]);

        $user->delete();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.contacts.show', [$contact->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('contact.name', '架空 太郎')
                ->where('contact.member_code', null)
            );
    }
}
