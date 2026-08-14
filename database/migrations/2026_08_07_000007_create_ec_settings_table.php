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
        Schema::create('ec_settings', function (Blueprint $table) {
            $table->comment('EC基本設定（単一行。idは常に1）');
            $table->id();
            $table->unsignedInteger('free_shipping_threshold')->default(10000)->comment('送料無料となる購入金額（税込）');
            $table->unsignedInteger('cod_fee')->default(330)->comment('代引き手数料');
            $table->text('bank_transfer_note')->comment('銀行振込の案内文');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ec_settings');
    }
};
