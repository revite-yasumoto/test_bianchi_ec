<?php

declare(strict_types=1);

namespace App\Models;

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

        return $query->where('display_start_on', '<=', $today)
            ->where('display_end_on', '>=', $today);
    }
}
