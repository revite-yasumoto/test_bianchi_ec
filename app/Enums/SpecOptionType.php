<?php

declare(strict_types=1);

namespace App\Enums;

enum SpecOptionType: string
{
    case Size = 'size';
    case Color = 'color';

    public function label(): string
    {
        return match ($this) {
            self::Size => 'サイズ',
            self::Color => 'カラー',
        };
    }
}
