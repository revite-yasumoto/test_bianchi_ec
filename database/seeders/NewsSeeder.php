<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['date' => '2026-08-03', 'category' => '新商品', 'title' => '2026年モデル ROADSTER RC7 の取り扱いを開始しました'],
            ['date' => '2026-07-28', 'category' => 'お知らせ', 'title' => '夏季休業期間中の出荷スケジュールについて'],
            ['date' => '2026-07-20', 'category' => '商品情報', 'title' => 'アパレルコレクションにサイズXLを追加しました'],
            ['date' => '2026-07-11', 'category' => '新商品', 'title' => 'サーマルボトル 600ml アクアカラー入荷'],
            ['date' => '2026-07-02', 'category' => 'お知らせ', 'title' => 'ランキング集計方法の変更について'],
        ];

        foreach ($items as $item) {
            News::query()->firstOrCreate(
                ['published_on' => $item['date'], 'title' => $item['title']],
                [
                    'category' => $item['category'],
                    'body' => $item['title'].'の詳細です（デモ用ダミーテキスト）。',
                    'is_published' => true,
                ]
            );
        }
    }
}
