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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('failed_login_attempts')
                ->default(0)
                ->after('status')
                ->comment('ロックに至るまでの連続ログイン失敗回数');

            // 解除時刻は2038年以降を取りうるため timestamp 型を使わない
            $table->dateTime('locked_until')
                ->nullable()
                ->after('failed_login_attempts')
                ->comment('ログインを拒否する期限。nullはロックなし');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['failed_login_attempts', 'locked_until']);
        });
    }
};
