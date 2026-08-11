<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\NoticeState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notice\SaveNoticeRequest;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): Response
    {
        $notices = Notice::query()
            ->orderByDesc('display_start_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->through(function (Notice $notice): array {
                $state = $notice->state();

                return [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'body' => $notice->body,
                    'display_start_on' => $notice->display_start_on->toDateString(),
                    'display_end_on' => $notice->display_end_on->toDateString(),
                    'period_label' => sprintf(
                        '%s - %s',
                        $notice->display_start_on->format('Y.m.d'),
                        $notice->display_end_on->format('Y.m.d'),
                    ),
                    'state' => $state->value,
                    'state_label' => $state->label(),
                    'state_tone' => $this->toneOf($state),
                ];
            });

        return Inertia::render('admin/Notice/Index', [
            'notices' => $notices,
        ]);
    }

    public function store(SaveNoticeRequest $request): RedirectResponse
    {
        Notice::query()->create($request->validated());

        return back();
    }

    public function update(SaveNoticeRequest $request, Notice $notice): RedirectResponse
    {
        $notice->update($request->validated());

        return back();
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return back();
    }

    /**
     * @return array{fg: string, bg: string}
     */
    private function toneOf(NoticeState $state): array
    {
        [$fg, $bg] = $state->color();

        return ['fg' => $fg, 'bg' => $bg];
    }
}
