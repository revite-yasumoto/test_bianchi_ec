<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * デモ用の管理者5件を投入する。パスワードは環境変数 ADMIN_SEED_PASSWORD から読み、コードに直書きしない。
     */
    public function run(): void
    {
        // config()の第2引数はキー自体が存在しない場合のみ適用され、
        // ADMIN_SEED_PASSWORD未設定時（値はnullでキーは存在）には効かないため ?? で明示的にフォールバックする
        $password = Hash::make(config('app.admin_seed_password') ?? 'password');

        $admins = [
            ['admin_code' => 'admin', 'name' => 'デモ管理者', 'email' => 'demo-admin@bianchi.demo'],
            ['admin_code' => 'A-001', 'name' => '管理 太郎', 'email' => 'admin@bianchi.demo'],
            ['admin_code' => 'A-002', 'name' => '運用 花子', 'email' => 'ops@bianchi.demo'],
            ['admin_code' => 'A-003', 'name' => '在庫 次郎', 'email' => 'stock@bianchi.demo'],
            ['admin_code' => 'A-004', 'name' => 'CS 三郎', 'email' => 'cs@bianchi.demo'],
        ];

        foreach ($admins as $admin) {
            Admin::query()->firstOrCreate(
                ['admin_code' => $admin['admin_code']],
                [
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'password' => $password,
                ]
            );
        }
    }
}
