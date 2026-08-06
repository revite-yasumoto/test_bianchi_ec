<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsCategory: string
{
    case NewProduct = '新商品';
    case Notice = 'お知らせ';
    case ProductInfo = '商品情報';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * バッジの [文字色, 背景色] を返す。
     *
     * @return array{0: string, 1: string}
     */
    public function color(): array
    {
        return match ($this) {
            self::NewProduct => ['#2F6F86', '#E7F0F4'],
            self::Notice => ['#B0521F', '#FDF0E2'],
            self::ProductInfo => ['#2b6f64', '#E4F2EF'],
        };
    }
}
