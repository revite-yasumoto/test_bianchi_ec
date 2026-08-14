<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Region;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegionTest extends TestCase
{
    /**
     * 各地方の最初と最後の都道府県コード（JIS X 0401）。
     *
     * @return array<string, array{0: int, 1: Region}>
     */
    public static function 境界の都道府県コード(): array
    {
        return [
            '北海道' => [1, Region::Hokkaido],
            '青森県' => [2, Region::Tohoku],
            '福島県' => [7, Region::Tohoku],
            '茨城県' => [8, Region::Kanto],
            '神奈川県' => [14, Region::Kanto],
            '新潟県' => [15, Region::Chubu],
            '愛知県' => [23, Region::Chubu],
            '三重県' => [24, Region::Kinki],
            '和歌山県' => [30, Region::Kinki],
            '鳥取県' => [31, Region::Chugoku],
            '山口県' => [35, Region::Chugoku],
            '徳島県' => [36, Region::Shikoku],
            '高知県' => [39, Region::Shikoku],
            '福岡県' => [40, Region::Kyushu],
            '沖縄県' => [47, Region::Kyushu],
        ];
    }

    #[Test]
    #[DataProvider('境界の都道府県コード')]
    public function 都道府県コードから所属する地方が決まる(int $prefectureId, Region $expected): void
    {
        $this->assertSame($expected, Region::of($prefectureId));
    }

    #[Test]
    public function 見出しの選択肢は八件で都道府県コードの順に並ぶ(): void
    {
        $options = Region::options();

        $this->assertCount(8, $options);
        $this->assertSame(
            ['北海道', '東北', '関東', '中部', '近畿', '中国', '四国', '九州・沖縄'],
            array_column($options, 'label'),
        );
    }

    #[Test]
    public function 全ての都道府県がいずれかの地方に属する(): void
    {
        $counts = [];

        foreach (range(1, 47) as $prefectureId) {
            $counts[Region::of($prefectureId)->value] = ($counts[Region::of($prefectureId)->value] ?? 0) + 1;
        }

        $this->assertCount(8, $counts);
        $this->assertSame(47, array_sum($counts));
    }
}
