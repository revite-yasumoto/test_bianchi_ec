<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Contact\BuildContactFilter;
use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Contact\UpdateContactRequest;
use App\Models\Admin;
use App\Models\Contact;
use App\Services\Admin\Contact\UpdateContactHandlingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    private const PER_PAGE = 50;

    private const EXCERPT_LENGTH = 60;

    public function index(Request $request, BuildContactFilter $buildFilter): Response
    {
        $filters = $buildFilter->filtersOf($request);

        $contacts = $buildFilter($filters)
            ->select(['id', 'created_at', 'name', 'email', 'product_name', 'product_code', 'body', 'status'])
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Contact $contact): array => [
                'id' => $contact->id,
                'received_at' => $contact->created_at->format('Y.m.d H:i'),
                'name' => $contact->name,
                'email' => $contact->email,
                'body_excerpt' => $this->excerptOf($contact->body),
                'product_name' => $contact->product_name,
                'product_code' => $contact->product_code,
                'status' => $contact->status->value,
                'status_label' => $contact->status->label(),
                'status_tone' => $this->toneOf($contact->status),
            ]);

        return Inertia::render('admin/Contact/Index', [
            'contacts' => $contacts,
            'filters' => $filters,
            // 表示中のタブの絞り込み前の件数。タブの振り分けは BuildContactFilter に寄せる
            'totalCount' => $buildFilter(['tab' => $filters['tab']])->count(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(Contact $contact): Response
    {
        $contact->load(['user:id,member_code', 'product:id,name,is_published', 'handledAdmin:id,name']);

        return Inertia::render('admin/Contact/Show', [
            'contact' => [
                'id' => $contact->id,
                'contact_number' => $contact->contact_number,
                'received_at' => $contact->created_at->format('Y.m.d H:i'),
                'name' => $contact->name,
                'email' => $contact->email,
                'body' => $contact->body,
                'member_code' => $contact->user?->member_code,
                'product' => $contact->product === null ? null : [
                    'id' => $contact->product->id,
                    'name' => $contact->product->name,
                    // 非公開商品はフロントの商品詳細が404になるため、リンクの出し分けに使う
                    'is_published' => $contact->product->is_published,
                ],
                'product_name' => $contact->product_name,
                'product_code' => $contact->product_code,
                'status' => $contact->status->value,
                'status_label' => $contact->status->label(),
                'status_tone' => $this->toneOf($contact->status),
                'admin_note' => $contact->admin_note,
                'handled_at' => $contact->handled_at?->format('Y.m.d H:i'),
                'handled_admin_name' => $contact->handledAdmin?->name,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(
        UpdateContactRequest $request,
        Contact $contact,
        UpdateContactHandlingService $service,
    ): RedirectResponse {
        /** @var Admin|null $admin */
        $admin = $request->user('admin');

        $service->update(
            $contact,
            $request->enum('status', ContactStatus::class),
            $request->filled('admin_note') ? $request->string('admin_note')->toString() : null,
            $admin,
        );

        return redirect()->route('admin.contacts.show', $contact);
    }

    /**
     * 一覧の本文列は文字数で切る（`Str::limit` は表示幅基準のため、全角では半分の文字数で切れる）。
     */
    private function excerptOf(string $body): string
    {
        return mb_strlen($body) > self::EXCERPT_LENGTH
            ? mb_substr($body, 0, self::EXCERPT_LENGTH).'…'
            : $body;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (ContactStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            ContactStatus::cases(),
        );
    }

    /**
     * @return array{fg: string, bg: string}
     */
    private function toneOf(ContactStatus $status): array
    {
        [$fg, $bg] = $status->color();

        return ['fg' => $fg, 'bg' => $bg];
    }
}
