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
}
