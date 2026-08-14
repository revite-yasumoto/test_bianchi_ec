<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => '銀行振込（前払い）',
            self::Cod => '代金引換',
        };
    }

    /**
     * 注文確定時に付与するステータス。前払いは入金確認を挟むため入金待ちから始まる。
     */
    public function initialOrderStatus(): OrderStatus
    {
        return match ($this) {
            self::BankTransfer => OrderStatus::AwaitingPayment,
            self::Cod => OrderStatus::Received,
        };
    }
}
