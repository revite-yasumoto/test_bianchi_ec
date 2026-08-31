<?php

declare(strict_types=1);

namespace App\Enums;

enum StockFilter: string
{
    case InStock = 'in_stock';
    case SoldOut = 'sold_out';

    public function label(): string
    {
        return match ($this) {
            self::InStock => '在庫あり',
            self::SoldOut => '在庫切れ',
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
            fn (self $filter): array => ['value' => $filter->value, 'label' => $filter->label()],
            self::cases(),
        );
    }
}
