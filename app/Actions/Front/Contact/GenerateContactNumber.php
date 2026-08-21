<?php

declare(strict_types=1);

namespace App\Actions\Front\Contact;

use App\Models\Contact;
use Carbon\CarbonImmutable;

class GenerateContactNumber
{
    /** 注文番号（BNC）と同じ体系で、問い合わせであることを示す接頭辞 */
    private const PREFIX = 'INQ';

    /**
     * `INQ-YYMM-NNNN` 形式の問い合わせ番号を採番する。`NNNN` は当月内の連番。
     *
     * 当月の最大番号を排他ロックしてから +1 する。件数を数えて +1 する方式では、
     * 同じ件数を読んだ同時送信の2件が同じ番号になる。呼び出し側でトランザクションを開いていること。
     *
     * 最大値の判定は文字列の辞書順に依存するため、連番は4桁（月あたり9999件）を上限とする。
     * これを超えると桁の少ない番号が最大と判定され、一意制約に阻まれて送信が通らなくなる。
     */
    public function __invoke(CarbonImmutable $sentAt): string
    {
        $prefix = sprintf('%s-%s-', self::PREFIX, $sentAt->format('ym'));

        $latest = Contact::query()
            ->where('contact_number', 'like', $prefix.'%')
            ->orderByDesc('contact_number')
            ->lockForUpdate()
            ->value('contact_number');

        $sequence = $latest === null
            ? 1
            : (int) substr($latest, strlen($prefix)) + 1;

        return $prefix.sprintf('%04d', $sequence);
    }
}
