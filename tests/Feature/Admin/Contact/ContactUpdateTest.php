<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Contact;

use App\Enums\ContactStatus;
use App\Models\Admin;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 問い合わせは Factory で用意する。送信の経路（`POST /contact`）は
 * `Tests\Feature\Front\Contact\ContactStoreTest` が検証している。
 */
class ContactUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-21 10:00:00');
        $this->admin = Admin::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function update(Contact $contact, array $payload): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->put(route('admin.contacts.update', [$contact->id]), $payload);
    }

    #[Test]
    public function 対応ステータスと対応メモを更新できる(): void
    {
        $contact = Contact::factory()->create(['status' => ContactStatus::Unhandled]);

        $this->update($contact, [
            'status' => ContactStatus::InProgress->value,
            'admin_note' => '在庫を確認中',
        ])->assertRedirect(route('admin.contacts.show', [$contact->id]));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => ContactStatus::InProgress->value,
            'admin_note' => '在庫を確認中',
        ]);
    }

    #[Test]
    public function 未認証では対応状況を更新できない(): void
    {
        $contact = Contact::factory()->create(['status' => ContactStatus::Unhandled]);

        $this->put(route('admin.contacts.update', [$contact->id]), [
            'status' => ContactStatus::Handled->value,
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => ContactStatus::Unhandled->value,
        ]);
    }

    #[Test]
    public function 更新した管理者が記録される(): void
    {
        $contact = Contact::factory()->create();

        $this->update($contact, ['status' => ContactStatus::InProgress->value]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'handled_admin_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function 対応済みへの変更で対応日時が操作時刻で記録される(): void
    {
        $contact = Contact::factory()->create(['status' => ContactStatus::Unhandled]);

        $this->update($contact, ['status' => ContactStatus::Handled->value]);

        $this->assertSame(
            '2026-08-21 10:00:00',
            $contact->refresh()->handled_at?->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function 対応済みから他の区分へ戻すと対応日時が消える(): void
    {
        $contact = Contact::factory()->create([
            'status' => ContactStatus::Handled,
            'handled_at' => '2026-08-18 11:20:00',
        ]);

        $this->update($contact, ['status' => ContactStatus::InProgress->value]);

        $this->assertNull($contact->refresh()->handled_at);
    }

    #[Test]
    public function 同じ区分のまま対応メモだけを更新しても対応日時は変わらない(): void
    {
        $contact = Contact::factory()->create([
            'status' => ContactStatus::Handled,
            'handled_at' => '2026-08-18 11:20:00',
        ]);

        $this->update($contact, [
            'status' => ContactStatus::Handled->value,
            'admin_note' => 'メモだけ更新',
        ]);

        $this->assertSame(
            '2026-08-18 11:20:00',
            $contact->refresh()->handled_at?->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function どの区分からどの区分へも変更できる(): void
    {
        $contact = Contact::factory()->create(['status' => ContactStatus::Handled]);

        $this->update($contact, ['status' => ContactStatus::Unhandled->value]);

        $this->assertSame(ContactStatus::Unhandled, $contact->refresh()->status);
    }

    #[Test]
    public function 不正な対応ステータスは拒否される(): void
    {
        $contact = Contact::factory()->create(['status' => ContactStatus::Unhandled]);

        $this->update($contact, ['status' => 'unknown'])
            ->assertSessionHasErrors('status');

        $this->assertSame(ContactStatus::Unhandled, $contact->refresh()->status);
    }

    #[Test]
    public function 上限を超える対応メモは拒否される(): void
    {
        $contact = Contact::factory()->create();

        $this->update($contact, [
            'status' => ContactStatus::InProgress->value,
            'admin_note' => str_repeat('あ', 2001),
        ])->assertSessionHasErrors('admin_note');

        $this->assertNull($contact->refresh()->admin_note);
    }

    #[Test]
    public function 対応メモを送らない更新では既存のメモが消える(): void
    {
        $contact = Contact::factory()->create([
            'status' => ContactStatus::Unhandled,
            'admin_note' => '在庫を確認中',
        ]);

        $this->update($contact, ['status' => ContactStatus::InProgress->value]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => ContactStatus::InProgress->value,
            'admin_note' => null,
        ]);
    }

    #[Test]
    public function 送信者が入力した内容は更新の対象にならない(): void
    {
        $contact = Contact::factory()->create([
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'body' => 'もとの本文です。',
            'product_name' => '架空ジャージ 2026',
        ]);

        $this->update($contact, [
            'status' => ContactStatus::InProgress->value,
            'name' => '書き換え',
            'email' => 'attacker@example.test',
            'body' => '書き換えた本文',
            'product_name' => '書き換えた商品名',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => '架空 太郎',
            'email' => 'taro@example.test',
            'body' => 'もとの本文です。',
            'product_name' => '架空ジャージ 2026',
        ]);
    }
}
