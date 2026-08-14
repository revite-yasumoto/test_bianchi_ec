<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\NewsCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\News\SaveNewsRequest;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): Response
    {
        $news = News::query()
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (News $item): array => [
                'id' => $item->id,
                'published_on' => $item->published_on->format('Y.m.d'),
                'published_on_input' => $item->published_on->toDateString(),
                'category' => $item->category->value,
                'category_tone' => $this->toneOf($item->category),
                'title' => $item->title,
                'body' => $item->body,
                'is_published' => $item->is_published,
                'state_label' => $item->is_published ? '公開' : '非公開',
            ]);

        return Inertia::render('admin/News/Index', [
            'news' => $news,
            'categoryOptions' => array_column(NewsCategory::cases(), 'value'),
        ]);
    }

    public function store(SaveNewsRequest $request): RedirectResponse
    {
        News::query()->create($request->validated());

        return back();
    }

    public function update(SaveNewsRequest $request, News $news): RedirectResponse
    {
        $news->update($request->validated());

        return back();
    }

    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return back();
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
