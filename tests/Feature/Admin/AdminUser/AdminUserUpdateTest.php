<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AdminUser;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Admin $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['admin_code' => 'A-001']);
        $this->target = Admin::factory()->create([
            'admin_code' => 'A-002',
            'name' => '運用 花子',
            'email' => 'ops@bianchi.test',
        ]);
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
            'password' => '',
            'password_confirmation' => '',
        ], $overrides);
    }

    #[Test]
    public function 管理者編集画面に既存の値が渡される(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.admins.edit', $this->target))
            ->assertInertia(fn ($page) => $page
                ->component('admin/AdminUser/Form')
                ->where('admin.admin_code', 'A-002')
                ->where('admin.name', '運用 花子')
                ->where('admin.email', 'ops@bianchi.test')
            );
    }

    #[Test]
    public function 氏名とメールアドレスを更新できる(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.admins.update', $this->target),
            $this->payload(['name' => '運用 太郎', 'email' => 'ops2@bianchi.test']),
        );

        $response->assertRedirect(route('admin.admins.index'));
        $this->assertDatabaseHas('admins', [
            'id' => $this->target->id,
            'name' => '運用 太郎',
            'email' => 'ops2@bianchi.test',
        ]);
    }

    #[Test]
    public function パスワード未入力なら既存のパスワードが保持される(): void
    {
        $before = $this->target->password;

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.admins.update', $this->target),
            $this->payload(['name' => '運用 太郎']),
        );

        $this->assertSame($before, $this->target->refresh()->password);
    }

    #[Test]
    public function パスワードを入力すると変更される(): void
    {
        $this->actingAs($this->admin, 'admin')->put(
            route('admin.admins.update', $this->target),
            $this->payload([
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]),
        );

        $this->assertTrue(Hash::check('newpassword123', $this->target->refresh()->password));
    }

    #[Test]
    public function 自分自身のメールアドレスは重複として扱われない(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.admins.update', $this->target), $this->payload())
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function 他の管理者と同じメールアドレスには変更できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.admins.update', $this->target),
            $this->payload(['email' => $this->admin->email]),
        );

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function 短すぎるパスワードには変更できない(): void
    {
        $before = $this->target->password;

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.admins.update', $this->target),
            $this->payload(['password' => 'short12', 'password_confirmation' => 'short12']),
        );

        $response->assertSessionHasErrors('password');
        $this->assertSame($before, $this->target->refresh()->password);
    }

    #[Test]
    public function 未認証は管理者を更新できない(): void
    {
        $this->put(route('admin.admins.update', $this->target), $this->payload(['name' => '不正 更新']))
            ->assertRedirect(route('admin.login'));

        $this->assertSame('運用 花子', $this->target->refresh()->name);
    }
}
