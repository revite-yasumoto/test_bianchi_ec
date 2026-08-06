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
        Schema::create('contacts', function (Blueprint $table) {
            $table->comment('お問い合わせ');
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('ログイン中の会員');
            $table->string('name', 100);
            $table->string('email', 191);
            $table->string('product_name', 255)->nullable()->comment('対象商品（商品詳細から自動入力）');
            $table->text('body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
