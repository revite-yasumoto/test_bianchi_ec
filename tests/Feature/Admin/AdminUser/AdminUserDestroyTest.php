<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AdminUser;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserDestroyTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['admin_code' => 'A-001']);
    }

    #[Test]
    public function 他の管理者を削除できる(): void
    {
        $target = Admin::factory()->create(['admin_code' => 'A-002']);

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.admins.destroy', $target));

        $response->assertRedirect(route('admin.admins.index'));
        $this->assertDatabaseMissing('admins', ['id' => $target->id]);
    }

    #[Test]
    public function ログイン中の自分自身は削除できない(): void
    {
        Admin::factory()->create(['admin_code' => 'A-002']);

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.admins.destroy', $this->admin));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('admins', ['id' => $this->admin->id]);
    }

    #[Test]
    public function 管理者が1名のときは削除できない(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.admins.destroy', $this->admin));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseCount('admins', 1);
    }

    #[Test]
    public function 管理者を削除しても変更履歴は残り管理者への参照だけが外れる(): void
    {
        $target = Admin::factory()->create(['admin_code' => 'A-002']);
        $order = Order::factory()->create();
        $history = OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'admin_id' => $target->id,
            'from_status' => OrderStatus::Received,
            'to_status' => OrderStatus::Preparing,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.admins.destroy', $target));

        $this->assertDatabaseHas('order_status_histories', [
            'id' => $history->id,
            'admin_id' => null,
            'to_status' => OrderStatus::Preparing->value,
        ]);
    }

    #[Test]
    public function 未認証は管理者を削除できない(): void
    {
        $target = Admin::factory()->create(['admin_code' => 'A-002']);

        $this->delete(route('admin.admins.destroy', $target))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('admins', ['id' => $target->id]);
    }
}
