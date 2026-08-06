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
        Schema::create('products', function (Blueprint $table) {
            $table->comment('商品マスタ（在庫は在庫マスタに一元化）');
            $table->id();
            $table->string('product_code', 50)->unique()->comment('ユーザー入力の商品ID（SKUコードのベース）');
            $table->string('name', 255);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('price')->comment('価格（税込）');
            $table->text('description')->nullable();
            $table->boolean('has_sku')->default(false)->comment('SKUの有無');
            $table->boolean('is_published')->default(false)->comment('公開状態');
            $table->timestamps();

            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
