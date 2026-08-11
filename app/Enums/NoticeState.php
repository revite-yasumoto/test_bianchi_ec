<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 重要なお知らせの掲載状態。カラムとして保存せず、当日日付と掲載期間から算出する。
 */
enum NoticeState: string
{
    case Displaying = 'displaying';
    case Scheduled = 'scheduled';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Displaying => '掲載中',
            self::Scheduled => '予約',
            self::Ended => '掲載終了',
        };
    }

    /**
     * バッジの [文字色, 背景色] を返す。
     *
     * @return array{0: string, 1: string}
     */
    public function color(): array
    {
        return match ($this) {
            self::Displaying => ['#2b6f64', '#E4F2EF'],
            self::Scheduled => ['#2F6F86', '#E7F0F4'],
            self::Ended => ['#5e6b77', '#EFECE6'],
        };
    }
}
