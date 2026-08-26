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
        Schema::table('banners', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('subtitle')
                ->constrained()
                ->nullOnDelete()
                ->comment('訴求する商品。「見る」ボタンの遷移先');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
