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
        Schema::create('order_items', function (Blueprint $table) {
            $table->comment('注文明細。商品情報を注文時点のスナップショットとして保持する');
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // 商品スナップショット
            $table->string('product_code', 50)->comment('注文時点の商品ID');
            $table->string('product_name', 255)->comment('注文時点の商品名');
            $table->string('category_name', 50)->comment('注文時点のカテゴリ名');
            $table->string('sku_code', 80)->nullable();
            $table->string('size_name', 50)->nullable();
            $table->string('color_name', 50)->nullable();
            $table->string('product_image_path', 255)->nullable()->comment('注文時点のメイン画像パス');
            $table->unsignedInteger('unit_price')->comment('注文時点の単価');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('subtotal')->comment('unit_price × quantity');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
