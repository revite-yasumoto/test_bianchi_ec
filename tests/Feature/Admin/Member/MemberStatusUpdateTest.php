<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Member;

use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    #[Test]
    public function 会員を休会にできる(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.members.status.update', $user),
            ['status' => UserStatus::Suspended->value],
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(UserStatus::Suspended, $user->refresh()->status);
    }

    #[Test]
    public function 休会中の会員を有効に戻せる(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.members.status.update', $user),
            ['status' => UserStatus::Active->value],
        );

        $this->assertSame(UserStatus::Active, $user->refresh()->status);
    }

    #[Test]
    public function 休会にした会員はフロントでログインできなくなる(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.members.status.update', $user),
            ['status' => UserStatus::Suspended->value],
        );

        // actingAs は既定ガードを admin に切り替えるため、会員のログイン導線を試す前に認証状態を切り離す
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->flushSession();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function 不正なステータス値では更新できない(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.members.status.update', $user),
            ['status' => 'deleted'],
        );

        $response->assertSessionHasErrors('status');
        $this->assertSame(UserStatus::Active, $user->refresh()->status);
    }

    #[Test]
    public function 退会済みの会員はステータスを更新できない(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Withdrawn]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.members.status.update', $user), [
                'status' => UserStatus::Active->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(UserStatus::Withdrawn, $user->refresh()->status);
    }

    #[Test]
    public function 退会へのステータス変更は受け付けない(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.members.status.update', $user), [
                'status' => UserStatus::Withdrawn->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(UserStatus::Active, $user->refresh()->status);
    }

    #[Test]
    public function 未認証は会員ステータスを更新できない(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $this->put(route('admin.members.status.update', $user), [
            'status' => UserStatus::Suspended->value,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(UserStatus::Active, $user->refresh()->status);
    }
}
