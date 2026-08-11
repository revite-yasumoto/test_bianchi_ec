<?php

declare(strict_types=1);

namespace App\Actions\Admin\AdminUser;

use App\Models\Admin;

class GenerateAdminCode
{
    /** `A-` 形式の管理者が1件も無いときの開始番号 */
    private const START_NUMBER = 1;

    public function __invoke(): string
    {
        // ログインID用に `A-` 形式でない管理者コードも存在しうるため、採番対象を接頭辞で絞る
        $latest = Admin::query()
            ->where('admin_code', 'like', 'A-%')
            ->max('admin_code');

        $next = $latest === null
            ? self::START_NUMBER
            : ((int) substr($latest, 2)) + 1;

        return sprintf('A-%03d', $next);
    }
}
