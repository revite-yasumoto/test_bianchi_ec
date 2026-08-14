<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Models\Admin;
use Generator;

class AdminUserCsvExporter
{
    /**
     * 初期パスワード列はインポート専用のため書き出さない。
     *
     * @return array<int, string>
     */
    public function header(): array
    {
        return ['管理者ID', '氏名', 'メールアドレス', '登録日'];
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    public function rows(): Generator
    {
        foreach (Admin::query()->orderBy('admin_code')->cursor() as $admin) {
            yield [
                $admin->admin_code,
                $admin->name,
                $admin->email,
                $admin->created_at->format('Y-m-d'),
            ];
        }
    }
}
