<?php

declare(strict_types=1);

namespace App\Actions\Front\Auth;

use App\Models\User;

class GenerateMemberCode
{
    /** 会員が1件も無いときの開始番号 */
    private const START_NUMBER = 100001;

    public function __invoke(): string
    {
        // 会員IDは `M-` + 6桁ゼロ埋めで桁が揃うため、文字列としての最大値が採番済みの最大連番と一致する
        $latest = User::query()->max('member_code');

        $next = $latest === null
            ? self::START_NUMBER
            : ((int) substr($latest, 2)) + 1;

        return sprintf('M-%06d', $next);
    }
}
