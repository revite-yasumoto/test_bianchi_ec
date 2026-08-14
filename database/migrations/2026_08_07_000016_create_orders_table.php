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
        Schema::create('orders', function (Blueprint $table) {
            $table->comment('注文。会員・配送先・金額・算出根拠を注文時点のスナップショットとして保持する');
            $table->id();
            $table->string('order_number', 30)->unique()->comment('注文番号（例: BNC-2608-1042）');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->index()->comment('App\\Enums\\OrderStatus');
            $table->string('payment_method', 20)->comment('bank_transfer / cod');
            $table->dateTime('ordered_at');
            $table->dateTime('cancelled_at')->nullable();

            // 会員スナップショット
            $table->string('member_code_snapshot', 20)->comment('注文時点の会員ID');
            $table->string('customer_name', 100)->comment('注文時点の氏名');
            $table->string('customer_name_kana', 100)->nullable()->comment('注文時点の氏名カナ');
            $table->string('customer_email', 191)->comment('注文時点のメールアドレス');
            $table->string('customer_tel', 20)->nullable()->comment('注文時点の電話番号');

            // 配送先スナップショット
            $table->string('shipping_recipient_name', 100);
            $table->string('shipping_postal_code', 8);
            $table->string('shipping_prefecture_name', 10)->comment('都道府県名（IDではなく名称を保存）');
            $table->string('shipping_city', 100);
            $table->string('shipping_address_line1', 255);
            $table->string('shipping_address_line2', 255)->nullable();
            $table->string('shipping_tel', 20);

            // 金額スナップショット
            $table->unsignedInteger('subtotal')->comment('商品合計');
            $table->unsignedInteger('shipping_fee')->comment('適用送料（無料適用後）');
            $table->unsignedInteger('cod_fee')->default(0)->comment('適用代引き手数料');
            $table->unsignedInteger('total')->comment('最終合計');

            // 算出根拠スナップショット
            $table->unsignedInteger('free_shipping_threshold')->comment('注文時点の送料無料しきい値');
            $table->unsignedInteger('shipping_fee_base')->comment('注文時点の都道府県の素の送料');
            $table->unsignedTinyInteger('delivery_days')->comment('注文時点の配送予定日数');
            $table->date('estimated_delivery_date')->comment('配達予定日');
            $table->text('bank_transfer_note')->nullable()->comment('銀行振込のとき注文時点の案内文');

            $table->timestamps();

            $table->index('ordered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
