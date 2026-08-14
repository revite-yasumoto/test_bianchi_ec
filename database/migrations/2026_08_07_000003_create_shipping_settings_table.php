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
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->comment('送料設定マスタ（都道府県ごとの送料・配送予定日数）');
            $table->id();
            $table->unsignedTinyInteger('prefecture_id')->unique();
            $table->foreign('prefecture_id')->references('id')->on('prefectures')->cascadeOnDelete();
            $table->unsignedInteger('fee')->comment('送料（円）');
            $table->unsignedTinyInteger('delivery_days')->comment('配送予定日数');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};
