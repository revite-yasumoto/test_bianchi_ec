<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Notice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NoticeSeeder extends Seeder
{
    /**
     * 掲載中・予約・掲載終了を1件ずつ投入する。
     */
    public function run(): void
    {
        $today = Carbon::today();

        $notices = [
            [
                'title' => '台風の影響による一部地域への配送遅延について',
                'start' => $today->copy()->subDays(2),
                'end' => $today->copy()->addDays(13),
            ],
            [
                'title' => 'システムメンテナンス実施のお知らせ',
                'start' => $today->copy()->addDays(5),
                'end' => $today->copy()->addDays(6),
            ],
            [
                'title' => '価格改定のご案内',
                'start' => $today->copy()->subDays(60),
                'end' => $today->copy()->subDays(30),
            ],
        ];

        foreach ($notices as $notice) {
            Notice::query()->firstOrCreate(
                ['title' => $notice['title']],
                [
                    'body' => $notice['title'].'の詳細です（デモ用ダミーテキスト）。',
                    'display_start_on' => $notice['start']->toDateString(),
                    'display_end_on' => $notice['end']->toDateString(),
                ]
            );
        }
    }
}
