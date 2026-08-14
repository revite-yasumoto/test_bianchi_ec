<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsCategory;
use Database\Factories\NewsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['published_on', 'category', 'title', 'body', 'is_published'])]
class News extends Model
{
    /** @use HasFactory<NewsFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'category' => NewsCategory::class,
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<News>  $query
     * @return Builder<News>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
