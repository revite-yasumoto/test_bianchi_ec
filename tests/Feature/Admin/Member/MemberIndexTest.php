<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Member;

use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberIndexTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    private function makeMember(string $code, string $name, string $email, string $createdAt = '2026-07-24 10:00:00'): User
    {
        return User::factory()->create([
            'member_code' => $code,
            'name' => $name,
            'email' => $email,
            'created_at' => $createdAt,
        ]);
    }

    #[Test]
    public function 会員一覧が表示される(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Member/Index')
                ->has('members.data', 1)
                ->where('members.data.0.member_code', 'M-100238')
                ->where('members.data.0.name', '山田 太郎')
                ->where('members.data.0.registered_on', '2026.07.24')
                ->where('members.data.0.status_label', '有効')
                ->where('totalCount', 1)
            );
    }

    #[Test]
    public function 氏名で絞り込める(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test');
        $this->makeMember('M-100301', '佐藤 花子', 'hanako@example.test');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index', ['q' => '佐藤']))
            ->assertInertia(fn ($page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.name', '佐藤 花子')
                ->where('totalCount', 2)
            );
    }

    #[Test]
    public function メールアドレスで絞り込める(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test');
        $this->makeMember('M-100301', '佐藤 花子', 'hanako@example.test');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index', ['q' => 'hanako']))
            ->assertInertia(fn ($page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.email', 'hanako@example.test')
            );
    }

    #[Test]
    public function 会員番号で絞り込める(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test');
        $this->makeMember('M-100301', '佐藤 花子', 'hanako@example.test');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index', ['q' => '100301']))
            ->assertInertia(fn ($page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.member_code', 'M-100301')
            );
    }

    #[Test]
    public function 休会中の会員もステータス付きで一覧に出る(): void
    {
        User::factory()->create([
            'member_code' => 'M-100412',
            'status' => UserStatus::Suspended,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index'))
            ->assertInertia(fn ($page) => $page
                ->where('members.data.0.status', UserStatus::Suspended->value)
                ->where('members.data.0.status_label', '休会')
            );
    }

    #[Test]
    public function 一覧は登録日の降順で並ぶ(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test', '2026-06-15 10:00:00');
        $this->makeMember('M-100301', '佐藤 花子', 'hanako@example.test', '2026-07-25 10:00:00');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index'))
            ->assertInertia(fn ($page) => $page
                ->where('members.data.0.member_code', 'M-100301')
                ->where('members.data.1.member_code', 'M-100238')
            );
    }

    #[Test]
    public function 該当がない場合は空の一覧になる(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index', ['q' => '存在しない会員']))
            ->assertInertia(fn ($page) => $page->has('members.data', 0));
    }

    #[Test]
    public function 一覧にパスワードハッシュが含まれない(): void
    {
        $this->makeMember('M-100238', '山田 太郎', 'taro@example.test');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.index'))
            ->assertInertia(fn ($page) => $page
                ->missing('members.data.0.password')
                ->missing('members.data.0.remember_token')
            );
    }

    #[Test]
    public function 未認証はログイン画面へリダイレクトされる(): void
    {
        $this->get(route('admin.members.index'))
            ->assertRedirect(route('admin.login'));
    }
}
