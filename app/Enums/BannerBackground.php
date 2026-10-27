<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 管理画面から CSS を直接入力させないため、背景は選択肢に限定する。
 */
enum BannerBackground: string
{
    case Blue = 'blue';
    case Rose = 'rose';
    case Coral = 'coral';
    case Teal = 'teal';
    case Charcoal = 'charcoal';

    public function label(): string
    {
        return match ($this) {
            self::Blue => 'ブルー',
            self::Rose => 'ローズ',
            self::Coral => 'コーラル',
            self::Teal => 'ティール',
            self::Charcoal => 'チャコール',
        };
    }

    /**
     * 描画に使う CSS の背景値。
     */
    public function gradient(): string
    {
        return match ($this) {
            self::Blue => 'linear-gradient(115deg,#2F6F86 0%,#274F60 60%,#1b3844 100%)',
            self::Rose => 'linear-gradient(115deg,#C25E86 0%,#8f3e60 60%,#5d2740 100%)',
            self::Coral => 'linear-gradient(115deg,#E1664B 0%,#b64530 55%,#7d2d1e 100%)',
            self::Teal => 'linear-gradient(115deg,#3E9E8F 0%,#2b6f64 60%,#1c4a43 100%)',
            self::Charcoal => 'linear-gradient(115deg,#5E6B77 0%,#3b444d 60%,#232323 100%)',
        };
    }
}
