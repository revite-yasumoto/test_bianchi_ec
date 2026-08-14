<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AdminUser;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserStoreTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['admin_code' => 'A-001']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => '運用 花子',
            'email' => 'ops@bianchi.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    #[Test]
    public function 管理者登録画面が表示される(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.admins.create'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/AdminUser/Form')
                ->where('admin', null)
            );
    }

    #[Test]
    public function 管理者を登録できる(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.admins.store'), $this->payload());

        $response->assertRedirect(route('admin.admins.index'));
        $this->assertDatabaseHas('admins', [
            'name' => '運用 花子',
            'email' => 'ops@bianchi.test',
        ]);
    }

    #[Test]
    public function 管理者番号は既存の最大連番の次で採番される(): void
    {
        Admin::factory()->create(['admin_code' => 'A-004']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.admins.store'), $this->payload());

        $this->assertDatabaseHas('admins', [
            'email' => 'ops@bianchi.test',
            'admin_code' => 'A-005',
        ]);
    }

    #[Test]
    public function 接頭辞のない管理者番号は採番の対象にならない(): void
    {
        // ログインID用の管理者コードが混在していても採番が壊れないことを担保する
        Admin::factory()->create(['admin_code' => 'admin']);
        Admin::factory()->create(['admin_code' => 'A-002']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.admins.store'), $this->payload());

        $this->assertDatabaseHas('admins', [
            'email' => 'ops@bianchi.test',
            'admin_code' => 'A-003',
        ]);
    }

    #[Test]
    public function 登録した管理者でログインできる(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.admins.store'), $this->payload());

        $created = Admin::query()->where('email', 'ops@bianchi.test')->sole();

        $this->assertTrue(Hash::check('password123', $created->password));

        $this->post(route('admin.login.store'), [
            'login_id' => 'ops@bianchi.test',
            'password' => 'password123',
        ])->assertSessionHasNoErrors();
    }

    #[Test]
    public function 重複したメールアドレスでは登録できない(): void
    {
        Admin::factory()->create(['email' => 'ops@bianchi.test']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.admins.store'), $this->payload());

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function パスワードが8文字未満では登録できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(
            route('admin.admins.store'),
            $this->payload(['password' => 'short12', 'password_confirmation' => 'short12']),
        );

        $response->assertSessionHasErrors('password');
    }

    #[Test]
    public function パスワードが確認用と一致しないと登録できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(
            route('admin.admins.store'),
            $this->payload(['password_confirmation' => 'different123']),
        );

        $response->assertSessionHasErrors('password');
    }

    #[Test]
    public function 氏名が未入力では登録できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.admins.store'), $this->payload(['name' => '']));

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function 未認証は管理者を登録できない(): void
    {
        $this->post(route('admin.admins.store'), $this->payload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('admins', ['email' => 'ops@bianchi.test']);
    }
}
