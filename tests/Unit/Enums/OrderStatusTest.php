<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    #[Test]
    public function label_returns_japanese_text_for_each_status(): void
    {
        $this->assertSame('注文受付', OrderStatus::Received->label());
        $this->assertSame('入金待ち', OrderStatus::AwaitingPayment->label());
        $this->assertSame('入金確認済み', OrderStatus::PaymentConfirmed->label());
        $this->assertSame('出荷準備中', OrderStatus::Preparing->label());
        $this->assertSame('出荷済み', OrderStatus::Shipped->label());
        $this->assertSame('キャンセル', OrderStatus::Cancelled->label());
    }

    #[Test]
    public function allowed_transitions_from_received(): void
    {
        $received = OrderStatus::Received;

        $this->assertTrue($received->canTransitionTo(OrderStatus::AwaitingPayment));
        $this->assertTrue($received->canTransitionTo(OrderStatus::PaymentConfirmed));
        $this->assertTrue($received->canTransitionTo(OrderStatus::Preparing));
        $this->assertTrue($received->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse($received->canTransitionTo(OrderStatus::Shipped));
    }

    #[Test]
    public function shipped_and_cancelled_are_terminal(): void
    {
        $this->assertSame([], OrderStatus::Shipped->allowedTransitions());
        $this->assertSame([], OrderStatus::Cancelled->allowedTransitions());
        $this->assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Received));
    }

    #[Test]
    public function color_returns_a_foreground_and_background_pair(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $color = $status->color();

            $this->assertCount(2, $color);
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $color[0]);
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $color[1]);
        }
    }
}
