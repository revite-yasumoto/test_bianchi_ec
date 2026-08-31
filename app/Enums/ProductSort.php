<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductSort: string
{
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';

    public function label(): string
    {
        return match ($this) {
            self::Newest => '新着順',
            self::PriceAsc => '価格が安い順',
            self::PriceDesc => '価格が高い順',
        };
    }

    /**
     * 並び順の指定。同じ値が並んだときの順序が実行ごとに変わらないよう、
     * 最後に `id` の降順を続ける前提で列を返す。
     *
     * @return list<array{0: string, 1: string}>
     */
    public function columns(): array
    {
        return match ($this) {
            self::Newest => [],
            self::PriceAsc => [['price', 'asc']],
            self::PriceDesc => [['price', 'desc']],
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
            fn (self $sort): array => ['value' => $sort->value, 'label' => $sort->label()],
            self::cases(),
        );
    }
}
