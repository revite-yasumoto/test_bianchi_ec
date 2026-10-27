<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 商品・バナー・カテゴリの画像で受け付ける条件を揃えるための唯一の定義。
 */
final class ImageUploadRules
{
    /** @var array<int, string> */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const MAX_KILOBYTES = 5120;

    public const MAX_DIMENSION = 4000;

    /**
     * @return array<int, string>
     */
    public static function file(): array
    {
        return [
            'image',
            'mimes:'.implode(',', self::EXTENSIONS),
            'max:'.self::MAX_KILOBYTES,
            'dimensions:max_width='.self::MAX_DIMENSION.',max_height='.self::MAX_DIMENSION,
        ];
    }

    /**
     * エラーメッセージに出す許可形式の表記（例: jpg・jpeg・png・webp）。
     */
    public static function extensionsLabel(): string
    {
        return implode('・', self::EXTENSIONS);
    }

    public static function maxMegabytes(): int
    {
        return intdiv(self::MAX_KILOBYTES, 1024);
    }
}
