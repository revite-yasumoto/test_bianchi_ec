<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            $table->string('pc_image_path', 255)
                ->nullable()
                ->after('background')
                ->comment('PC幅で表示する画像のパス');
            $table->string('sp_image_path', 255)
                ->nullable()
                ->after('pc_image_path')
                ->comment('スマホ幅で表示する画像のパス');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn(['pc_image_path', 'sp_image_path']);
        });
    }
};
