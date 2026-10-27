<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 投入済みの行が持つ CSS の値と、置き換える区分コードの対応。
     * Enum は将来変わりうるため、マイグレーション内に値を固定で持つ。
     *
     * @var array<string, string>
     */
    private const CODES = [
        'linear-gradient(115deg,#2F6F86 0%,#274F60 60%,#1b3844 100%)' => 'blue',
        'linear-gradient(115deg,#C25E86 0%,#8f3e60 60%,#5d2740 100%)' => 'rose',
        'linear-gradient(115deg,#E1664B 0%,#b64530 55%,#7d2d1e 100%)' => 'coral',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::CODES as $css => $code) {
            DB::table('banners')->where('background', $css)->update(['background' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::CODES as $css => $code) {
            DB::table('banners')->where('background', $code)->update(['background' => $css]);
        }
    }
};
