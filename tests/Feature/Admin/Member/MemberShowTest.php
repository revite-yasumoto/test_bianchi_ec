<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Member;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberShowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    #[Test]
    public function 会員詳細が表示される(): void
    {
        $user = User::factory()->create([
            'member_code' => 'M-100238',
            'name' => '山田 太郎',
            'name_kana' => 'ヤマダ タロウ',
            'email' => 'taro@example.test',
            'tel' => '090-0000-0000',
            'created_at' => '2026-07-24 10:00:00',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.show', $user))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Member/Show')
                ->where('member.member_code', 'M-100238')
                ->where('member.name', '山田 太郎')
                ->where('member.name_kana', 'ヤマダ タロウ')
                ->where('member.registered_on', '2026.07.24')
                ->where('member.status_label', '有効')
            );
    }

    #[Test]
    public function 配送先住所が都道府県名付きで表示される(): void
    {
        $user = User::factory()->create();
        $prefecture = Prefecture::query()->firstOrCreate(
            ['name' => '東京都'],
            ['sort_order' => 13],
        );
        UserAddress::factory()->create([
            'user_id' => $user->id,
            'label' => '自宅',
            'prefecture_id' => $prefecture->id,
            'is_default' => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.show', $user))
            ->assertInertia(fn ($page) => $page
                ->has('addresses', 1)
                ->where('addresses.0.label', '自宅')
                ->where('addresses.0.prefecture_name', '東京都')
                ->where('addresses.0.is_default', true)
            );
    }

    #[Test]
    public function 直近の注文が新しい順で表示される(): void
    {
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'BNC-0001-0001',
            'ordered_at' => '2026-06-15 10:00:00',
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'BNC-0001-0002',
            'ordered_at' => '2026-07-25 10:00:00',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.show', $user))
            ->assertInertia(fn ($page) => $page
                ->has('recentOrders', 2)
                ->where('recentOrders.0.order_number', 'BNC-0001-0002')
                ->where('recentOrders.1.order_number', 'BNC-0001-0001')
            );
    }

    #[Test]
    public function 他の会員の注文は表示されない(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Order::factory()->create([
            'user_id' => $other->id,
            'order_number' => 'BNC-0001-9999',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.show', $user))
            ->assertInertia(fn ($page) => $page->has('recentOrders', 0));
    }

    #[Test]
    public function 会員詳細にパスワードハッシュが含まれない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.members.show', $user))
            ->assertInertia(fn ($page) => $page
                ->missing('member.password')
                ->missing('member.remember_token')
            );
    }

    #[Test]
    public function 未認証は会員詳細を開けない(): void
    {
        $user = User::factory()->create();

        $this->get(route('admin.members.show', $user))
            ->assertRedirect(route('admin.login'));
    }
}
