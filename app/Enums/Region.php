<?php

declare(strict_types=1);

namespace App\Enums;

enum Region: string
{
    case Hokkaido = 'hokkaido';
    case Tohoku = 'tohoku';
    case Kanto = 'kanto';
    case Chubu = 'chubu';
    case Kinki = 'kinki';
    case Chugoku = 'chugoku';
    case Shikoku = 'shikoku';
    case Kyushu = 'kyushu';

    public function label(): string
    {
        return match ($this) {
            self::Hokkaido => '北海道',
            self::Tohoku => '東北',
            self::Kanto => '関東',
            self::Chubu => '中部',
            self::Kinki => '近畿',
            self::Chugoku => '中国',
            self::Shikoku => '四国',
            self::Kyushu => '九州・沖縄',
        };
    }

    /**
     * 都道府県コード（JIS X 0401）から所属する地方を返す。
     * `prefectures` はコードが JIS X 0401 に一致する不変のマスタのため、範囲で判定できる。
     */
    public static function of(int $prefectureId): self
    {
        return match (true) {
            $prefectureId <= 1 => self::Hokkaido,
            $prefectureId <= 7 => self::Tohoku,
            $prefectureId <= 14 => self::Kanto,
            $prefectureId <= 23 => self::Chubu,
            $prefectureId <= 30 => self::Kinki,
            $prefectureId <= 35 => self::Chugoku,
            $prefectureId <= 39 => self::Shikoku,
            default => self::Kyushu,
        };
    }

    /**
     * 見出しの並び順。都道府県コードの昇順に一致する。
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $region): array => ['value' => $region->value, 'label' => $region->label()],
            self::cases(),
        );
    }
}
