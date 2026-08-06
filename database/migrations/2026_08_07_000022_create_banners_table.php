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
        Schema::create('banners', function (Blueprint $table) {
            $table->comment('TOPメインビジュアル。管理UIは要件対象外のためSeederで投入する');
            $table->id();
            $table->string('tag', 50)->comment('上部の小ラベル');
            $table->string('title', 255)->comment('見出し（改行を含む）');
            $table->string('subtitle', 255)->nullable();
            $table->string('background', 255)->comment('背景スタイル値');
            $table->string('link_url', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
