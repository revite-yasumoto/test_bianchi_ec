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
        Schema::create('product_rankings', function (Blueprint $table) {
            $table->comment('ランキング集計結果。rank は MySQL 8.0 の予約語のため rank_position を使う');
            $table->id();
            $table->char('target_year_month', 7)->comment('集計対象月（例: 2026-07）');
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete()->comment('null=全体ランキング');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rank_position')->comment('順位');
            $table->dateTime('aggregated_at')->comment('集計実行日時');
            $table->timestamps();

            // 自動生成される索引名（テーブル名+全列名+_unique）はMySQLの識別子長上限64文字を超えるため、明示的に短い名前を付ける。
            $table->unique(['target_year_month', 'category_id', 'rank_position'], 'product_rankings_ym_cat_rank_unique');
            $table->index(['target_year_month', 'category_id'], 'product_rankings_ym_cat_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_rankings');
    }
};
