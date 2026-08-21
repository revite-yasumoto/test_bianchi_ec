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
        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('email')
                ->constrained()
                ->nullOnDelete()
                ->comment('商品詳細から遷移したときの対象商品');
            $table->string('status', 20)
                ->default('unhandled')
                ->after('body')
                ->comment('App\\Enums\\ContactStatus');
            $table->text('admin_note')
                ->nullable()
                ->after('status')
                ->comment('管理者の対応メモ');
            $table->foreignId('handled_admin_id')
                ->nullable()
                ->after('admin_note')
                ->constrained('admins')
                ->nullOnDelete()
                ->comment('最後に対応状況を更新した管理者');
            $table->dateTime('handled_at')
                ->nullable()
                ->after('handled_admin_id')
                ->comment('対応済みへ変更した日時');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('handled_admin_id');
            $table->dropColumn(['status', 'admin_note', 'handled_at']);
        });
    }
};
