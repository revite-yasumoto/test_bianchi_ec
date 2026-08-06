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
        Schema::create('spec_options', function (Blueprint $table) {
            $table->comment('規格管理（SKUバリエーションのサイズ・カラー）');
            $table->id();
            $table->string('type', 10)->comment('size / color');
            $table->string('name', 50)->comment('規格値（例: S、アクア）');
            $table->unsignedInteger('sort_order')->default(0)->comment('表示順');
            $table->timestamps();

            $table->unique(['type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spec_options');
    }
};
