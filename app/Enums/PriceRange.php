<?php

declare(strict_types=1);

namespace App\Enums;

enum PriceRange: string
{
    case Under10000 = 'under_10000';
    case From10000 = 'from_10000';
    case From50000 = 'from_50000';
    case From150000 = 'from_150000';

    public function label(): string
    {
        return match ($this) {
            self::Under10000 => '〜1万円',
            self::From10000 => '1万〜5万円',
            self::From50000 => '5万〜15万円',
            self::From150000 => '15万円〜',
        };
    }

    /**
     * 下限（含む）と上限（含まない）。上限のない区分は null を返す。
     *
     * @return array{0: int, 1: int|null}
     */
    public function bounds(): array
    {
        return match ($this) {
            self::Under10000 => [0, 10000],
            self::From10000 => [10000, 50000],
            self::From50000 => [50000, 150000],
            self::From150000 => [150000, null],
        };
    }

    /**
     * 画面へ渡す選択肢。
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $range): array => ['value' => $range->value, 'label' => $range->label()],
            self::cases(),
        );
    }
}
