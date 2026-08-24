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
            $table->date('birthday')
                ->nullable()
                ->after('tel')
                ->comment('生年月日。任意項目のため未入力はnull');

            $table->string('gender', 10)
                ->nullable()
                ->after('birthday')
                ->comment('性別（App\Enums\Gender）。未回答はnull');

            $table->boolean('is_newsletter_subscribed')
                ->default(true)
                ->after('gender')
                ->comment('メールマガジンの購読可否');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['birthday', 'gender', 'is_newsletter_subscribed']);
        });
    }
};
