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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->comment('SKU。SKUなし商品もsize/color=nullの1件を持つ');
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('branch_code', 20)->nullable()->comment('枝番（SKUあり商品のみ）');
            $table->string('sku_code', 80)->nullable()->unique()->comment('商品ID-枝番。取扱対象外・SKUなしはnull');
            $table->string('size_name', 50)->nullable();
            $table->string('color_name', 50)->nullable();
            $table->boolean('is_available')->default(true)->comment('false=規格なし（取扱対象外）');
            $table->timestamps();

            $table->unique(['product_id', 'size_name', 'color_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
