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
        Schema::create('notices', function (Blueprint $table) {
            $table->comment('重要なお知らせ。掲載状態はカラムを持たず掲載期間から算出する');
            $table->id();
            $table->string('title', 255);
            $table->text('body');
            $table->date('display_start_on')->comment('掲載開始日');
            $table->date('display_end_on')->comment('掲載終了日');
            $table->timestamps();

            $table->index(['display_start_on', 'display_end_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
