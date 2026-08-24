<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Enums\NoticeState;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): Response
    {
        $notices = Notice::query()
            ->displayable()
            ->select(['id', 'title', 'display_start_on', 'display_end_on'])
            ->orderByDesc('display_start_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (Notice $notice): array => $this->summary($notice));

        return Inertia::render('front/Notice/Index', [
            'notices' => $notices,
        ]);
    }

    public function show(Notice $notice): Response
    {
        abort_unless($notice->state() === NoticeState::Displaying, 404);

        return Inertia::render('front/Notice/Show', [
            'notice' => [
                ...$this->summary($notice),
                'body' => $notice->body,
            ],
        ]);
    }

    /**
     * @return array{id: int, title: string, period_start: string, period_start_iso: string, period_end: string, period_end_iso: string}
     */
    private function summary(Notice $notice): array
    {
        return [
            'id' => $notice->id,
            'title' => $notice->title,
            'period_start' => $notice->display_start_on->format('Y.m.d'),
            'period_start_iso' => $notice->display_start_on->toDateString(),
            'period_end' => $notice->display_end_on->format('Y.m.d'),
            'period_end_iso' => $notice->display_end_on->toDateString(),
        ];
    }
}
