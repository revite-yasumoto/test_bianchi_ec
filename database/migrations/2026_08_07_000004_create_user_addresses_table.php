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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->comment('会員の配送先住所');
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100)->comment('表示名（例: 自宅）');
            $table->string('recipient_name', 100)->comment('宛名');
            $table->string('postal_code', 8);
            $table->unsignedTinyInteger('prefecture_id');
            $table->foreign('prefecture_id')->references('id')->on('prefectures')->restrictOnDelete();
            $table->string('city', 100)->comment('市区町村');
            $table->string('address_line1', 255)->comment('番地');
            $table->string('address_line2', 255)->nullable()->comment('建物名・部屋番号');
            $table->string('tel', 20);
            $table->boolean('is_default')->default(false)->comment('既定の配送先');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
