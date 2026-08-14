<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Enums\NewsCategory;
use App\Http\Controllers\Controller;
use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): Response
    {
        $news = News::query()
            ->published()
            ->select(['id', 'published_on', 'category', 'title', 'body'])
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (News $row): array => [
                'id' => $row->id,
                'published_on' => $row->published_on->format('Y.m.d'),
                'published_on_iso' => $row->published_on->toDateString(),
                'category' => $row->category->value,
                'category_tone' => $this->toneOf($row->category),
                'title' => $row->title,
                'body' => $row->body,
            ]);

        return Inertia::render('front/News/Index', [
            'news' => $news,
        ]);
    }

    /**
     * @return array{fg: string, bg: string}
     */
    private function toneOf(NewsCategory $category): array
    {
        [$fg, $bg] = $category->color();

        return ['fg' => $fg, 'bg' => $bg];
    }
}
