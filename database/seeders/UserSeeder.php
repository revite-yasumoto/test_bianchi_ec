<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Prefecture;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * デモ用の会員6件を投入する。先頭の会員には配送先住所を2件付与する。
     */
    public function run(): void
    {
        $members = [
            ['code' => 'M-100238', 'name' => '山田 太郎', 'email' => 'taro@example.com'],
            ['code' => 'M-100301', 'name' => '佐藤 花子', 'email' => 'hanako@example.com'],
            ['code' => 'M-100355', 'name' => '鈴木 一郎', 'email' => 'ichiro@example.com'],
            ['code' => 'M-100412', 'name' => '田中 美咲', 'email' => 'misaki@example.com', 'status' => 'suspended'],
            ['code' => 'M-100480', 'name' => '高橋 健', 'email' => 'ken@example.com'],
            ['code' => 'M-100502', 'name' => '伊藤 彩', 'email' => 'aya@example.com'],
        ];

        $password = Hash::make('password');
        $tokyo = Prefecture::query()->where('name', '東京都')->first();
        $hokkaido = Prefecture::query()->where('name', '北海道')->first();

        foreach ($members as $index => $data) {
            $user = User::query()->firstOrCreate(
                ['member_code' => $data['code']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                    'status' => $data['status'] ?? 'active',
                ]
            );

            if ($index === 0 && $tokyo !== null && $hokkaido !== null) {
                UserAddress::query()->firstOrCreate(
                    ['user_id' => $user->id, 'label' => '自宅'],
                    [
                        'recipient_name' => $user->name,
                        'postal_code' => '150-0041',
                        'prefecture_id' => $tokyo->id,
                        'city' => '渋谷区神南',
                        'address_line1' => '1-2-3 サイクルレジデンス404',
                        'tel' => '090-1234-5678',
                        'is_default' => true,
                    ]
                );

                UserAddress::query()->firstOrCreate(
                    ['user_id' => $user->id, 'label' => '実家'],
                    [
                        'recipient_name' => $user->name,
                        'postal_code' => '060-0001',
                        'prefecture_id' => $hokkaido->id,
                        'city' => '札幌市中央区北1条西',
                        'address_line1' => '5-8-1',
                        'tel' => '011-000-0000',
                        'is_default' => false,
                    ]
                );
            }
        }
    }
}
