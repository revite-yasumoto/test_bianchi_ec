<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'contacts_contact_number_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('contact_number', 20)
                ->nullable()
                ->after('id')
                ->comment('問い合わせ番号（INQ-YYMM-NNNN）');
            $table->string('product_code', 50)
                ->nullable()
                ->after('product_name')
                ->comment('対象商品の商品識別コード。商品削除後も残す');
        });

        $this->backfillContactNumbers();

        // 既存行を埋めてから NOT NULL と一意制約を付ける（採番漏れの行を制約で検出できるようにする）
        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('contact_number', 20)
                ->nullable(false)
                ->comment('問い合わせ番号（INQ-YYMM-NNNN）')
                ->change();
            $table->unique('contact_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DDL は暗黙コミットするため、up() が途中で失敗すると一意制約だけが無い状態が残る
        if (Schema::hasIndex('contacts', self::UNIQUE_INDEX)) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->dropUnique(['contact_number']);
            });
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn(['contact_number', 'product_code']);
        });
    }

    /**
     * 既存行に、受信月と月内の並び順から採番する。
     * 主キー順で走査する（受信日時は主キーと同じ順に増えるため、受信順の連番になる）。
     */
    private function backfillContactNumbers(): void
    {
        $sequences = [];

        DB::table('contacts')
            ->select(['id', 'created_at'])
            ->chunkById(100, function (iterable $contacts) use (&$sequences): void {
                foreach ($contacts as $contact) {
                    $yearMonth = CarbonImmutable::parse($contact->created_at)->format('ym');
                    $sequences[$yearMonth] = ($sequences[$yearMonth] ?? 0) + 1;

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update([
                            'contact_number' => sprintf('INQ-%s-%04d', $yearMonth, $sequences[$yearMonth]),
                        ]);
                }
            });
    }
};
