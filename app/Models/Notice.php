<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NoticeState;
use Carbon\CarbonImmutable;
use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'body', 'display_start_on', 'display_end_on'])]
class Notice extends Model
{
    /** @use HasFactory<NoticeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_start_on' => 'date',
            'display_end_on' => 'date',
        ];
    }

    /**
     * 掲載期間内（掲載中）のレコードに絞り込む。
     *
     * @param  Builder<Notice>  $query
     * @return Builder<Notice>
     */
    public function scopeDisplayable(Builder $query): Builder
    {
        $today = now()->toDateString();

        // 日時付きで格納されるドライバでも掲載開始日・掲載終了日の当日を含めるため、日付として比較する
        return $query->whereDate('display_start_on', '<=', $today)
            ->whereDate('display_end_on', '>=', $today);
    }

    /**
     * 掲載状態を当日日付と掲載期間から算出する。掲載開始日・掲載終了日の当日は「掲載中」とする。
     */
    public function state(): NoticeState
    {
        $today = CarbonImmutable::today();

        if ($this->display_start_on->greaterThan($today)) {
            return NoticeState::Scheduled;
        }

        if ($this->display_end_on->lessThan($today)) {
            return NoticeState::Ended;
        }

        return NoticeState::Displaying;
    }
}
