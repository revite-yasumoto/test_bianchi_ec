<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Models\User;
use Generator;

class MemberCsvExporter
{
    /**
     * 初期パスワード列はインポート専用のため書き出さない。
     *
     * @return array<int, string>
     */
    public function header(): array
    {
        return ['会員ID', '氏名', '氏名カナ', 'メールアドレス', '電話番号', 'ステータス', '登録日'];
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    public function rows(): Generator
    {
        foreach (User::query()->orderBy('id')->cursor() as $user) {
            yield [
                $user->member_code,
                $user->name,
                $user->name_kana ?? '',
                $user->email,
                $user->tel ?? '',
                $user->status->label(),
                $user->created_at->format('Y-m-d'),
            ];
        }
    }
}
