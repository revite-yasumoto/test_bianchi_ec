<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

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
            ->select(['id', 'title', 'body', 'display_start_on', 'display_end_on'])
            ->orderByDesc('display_start_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (Notice $notice): array => [
                'id' => $notice->id,
                'title' => $notice->title,
                'body' => $notice->body,
                'period_start' => $notice->display_start_on->format('Y.m.d'),
                'period_start_iso' => $notice->display_start_on->toDateString(),
                'period_end' => $notice->display_end_on->format('Y.m.d'),
                'period_end_iso' => $notice->display_end_on->toDateString(),
            ]);

        return Inertia::render('front/Notice/Index', [
            'notices' => $notices,
        ]);
    }
}
