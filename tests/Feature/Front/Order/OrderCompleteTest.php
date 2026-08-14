<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderCompleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $this->get(route('orders.complete', [$order]))->assertRedirect(route('login'));
    }

    #[Test]
    public function 自分の注文完了画面が表示される(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'BNC-2608-0042',
            'estimated_delivery_date' => '2026-08-16',
        ]);

        $this->actingAs($this->user)
            ->get(route('orders.complete', [$order]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('front/Order/Complete')
                ->where('order.order_number', 'BNC-2608-0042')
                ->where('order.estimated_delivery_date', '2026-08-16')
            );
    }

    #[Test]
    public function 他人の注文完了画面は表示できない(): void
    {
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)
            ->get(route('orders.complete', [$order]))
            ->assertForbidden();
    }

    #[Test]
    public function 銀行振込の注文には振込案内文が渡される(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::AwaitingPayment,
            'bank_transfer_note' => '注文時点の振込案内文',
        ]);

        $this->actingAs($this->user)
            ->get(route('orders.complete', [$order]))
            ->assertInertia(fn ($page) => $page
                ->where('order.payment_method', 'bank_transfer')
                ->where('order.bank_transfer_note', '注文時点の振込案内文')
            );
    }

    #[Test]
    public function 代引きの注文には振込案内文が渡されない(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_method' => PaymentMethod::Cod,
            'status' => OrderStatus::Received,
            'bank_transfer_note' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('orders.complete', [$order]))
            ->assertInertia(fn ($page) => $page
                ->where('order.payment_method', 'cod')
                ->where('order.bank_transfer_note', null)
            );
    }

    #[Test]
    public function リロードしても表示できて注文が増えない(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->get(route('orders.complete', [$order]))->assertOk();
        $this->actingAs($this->user)->get(route('orders.complete', [$order]))->assertOk();

        $this->assertDatabaseCount('orders', 1);
    }
}
