<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 変換前の行（CSS の値を持つ行）はモデルのキャストを通すと読めないため、DB::table で直接用意する。
 * 入口（管理画面・Seeder）はすでに区分コードで保存するため、変換前の行を作る入口が存在しない。
 */
class BannerBackgroundMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_27_000003_convert_banner_backgrounds_to_codes.php');
    }

    private function insertBanner(string $background): int
    {
        return DB::table('banners')->insertGetId([
            'tag' => 'ラベル',
            'title' => '見出し',
            'background' => $background,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function 投入済みの背景の配色の値が区分コードに置き換わる(): void
    {
        $blue = $this->insertBanner('linear-gradient(115deg,#2F6F86 0%,#274F60 60%,#1b3844 100%)');
        $rose = $this->insertBanner('linear-gradient(115deg,#C25E86 0%,#8f3e60 60%,#5d2740 100%)');
        $coral = $this->insertBanner('linear-gradient(115deg,#E1664B 0%,#b64530 55%,#7d2d1e 100%)');

        $this->migration()->up();

        $this->assertSame('blue', DB::table('banners')->where('id', $blue)->value('background'));
        $this->assertSame('rose', DB::table('banners')->where('id', $rose)->value('background'));
        $this->assertSame('coral', DB::table('banners')->where('id', $coral)->value('background'));
    }

    #[Test]
    public function 戻すと区分コードが元の配色の値に戻る(): void
    {
        $id = $this->insertBanner('rose');

        $this->migration()->down();

        $this->assertSame(
            'linear-gradient(115deg,#C25E86 0%,#8f3e60 60%,#5d2740 100%)',
            DB::table('banners')->where('id', $id)->value('background'),
        );
    }
}
