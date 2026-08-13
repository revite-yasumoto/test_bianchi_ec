<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Received = 'received';
    case AwaitingPayment = 'awaiting_payment';
    case PaymentConfirmed = 'payment_confirmed';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => '注文受付',
            self::AwaitingPayment => '入金待ち',
            self::PaymentConfirmed => '入金確認済み',
            self::Preparing => '出荷準備中',
            self::Shipped => '出荷済み',
            self::Cancelled => 'キャンセル',
        };
    }

    /**
     * バッジの [文字色, 背景色] を返す。
     *
     * @return array{0: string, 1: string}
     */
    public function color(): array
    {
        return match ($this) {
            self::Received => ['#274F60', '#E7F0F4'],
            self::AwaitingPayment => ['#B0521F', '#FDF0E2'],
            self::PaymentConfirmed => ['#2F6F86', '#E7F0F4'],
            self::Preparing => ['#7A5A1E', '#FBF1DD'],
            self::Shipped => ['#2b6f64', '#E4F2EF'],
            self::Cancelled => ['#8a4030', '#FBE7E1'],
        };
    }

    /**
     * このステータスから遷移可能なステータスの一覧を返す。
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Received => [self::AwaitingPayment, self::PaymentConfirmed, self::Preparing, self::Cancelled],
            self::AwaitingPayment => [self::PaymentConfirmed, self::Cancelled],
            self::PaymentConfirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Shipped, self::Cancelled],
            self::Shipped, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * 会員がマイページからキャンセルできるステータスか。
     * 入金確認済み以降は返金・出荷手配の判断が要るため、管理画面からの操作に限定する。
     */
    public function isCancelableByCustomer(): bool
    {
        return match ($this) {
            self::Received, self::AwaitingPayment => true,
            default => false,
        };
    }
}
