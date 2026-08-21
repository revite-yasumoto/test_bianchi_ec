<?php

declare(strict_types=1);

namespace App\Services\Admin\Contact;

use App\Enums\ContactStatus;
use App\Models\Admin;
use App\Models\Contact;

class UpdateContactHandlingService
{
    /**
     * 対応日時は対応済みへ変えたときに記録し、他の区分へ戻したときは消す。
     * 区分が変わらない更新（対応メモだけの修正）では、最初に対応済みにした日時を保つ。
     */
    public function update(Contact $contact, ContactStatus $status, ?string $adminNote, ?Admin $admin): void
    {
        $handledAt = match (true) {
            $contact->status === $status => $contact->handled_at,
            $status === ContactStatus::Handled => now(),
            default => null,
        };

        $contact->update([
            'status' => $status,
            'admin_note' => $adminNote,
            'handled_admin_id' => $admin?->id,
            'handled_at' => $handledAt,
        ]);
    }
}
