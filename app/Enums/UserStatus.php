<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Active => '有効',
            self::Suspended => '休会',
            self::Withdrawn => '退会',
        };
    }

    /**
     * 管理者が設定できるステータス。退会は会員自身の操作でのみ付き、戻すこともできない。
     *
     * @return list<self>
     */
    public static function administrable(): array
    {
        return [self::Active, self::Suspended];
    }
}
