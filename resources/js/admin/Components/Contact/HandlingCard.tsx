import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { cn } from '@/lib/utils';

type StatusOption = { value: string; label: string };

type HandlingCardProps = {
    contactId: number;
    contactNumber: string;
    currentStatus: string;
    statusOptions: StatusOption[];
    adminNote: string | null;
    /** 対応済みにした日時。他の区分へ戻すと消える */
    handledAt: string | null;
    handledAdminName: string | null;
};

/** `Admin\Contact\UpdateContactRequest` の `max` と揃える */
const ADMIN_NOTE_MAX = 2000;

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

export function HandlingCard({
    contactId,
    contactNumber,
    currentStatus,
    statusOptions,
    adminNote,
    handledAt,
    handledAdminName,
}: HandlingCardProps) {
    const { showToast } = useAdminToast();
    const { data, setData, put, processing, errors } = useForm({
        status: currentStatus,
        admin_note: adminNote ?? '',
    });
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const selectedLabel =
        statusOptions.find((option) => option.value === data.status)?.label ??
        '';
    // サーバー側の `max:2000` は mb_strlen 基準のため、UTF-16 のコード単位ではなく文字数で数える
    const noteLength = [...data.admin_note].length;
    const isNoteTooLong = noteLength > ADMIN_NOTE_MAX;

    const submit = () => {
        setIsConfirmOpen(false);

        put(route('admin.contacts.update', [contactId]), {
            preserveScroll: true,
            onSuccess: () => showToast('対応状況を更新しました'),
        });
    };

    return (
        <section className="rounded-xl border border-admin-line bg-white p-5">
            <h2 className="mb-2.5 text-[13px] font-extrabold text-admin-ink">
                対応状況
            </h2>

            <form
                onSubmit={(event) => {
                    event.preventDefault();

                    if (isNoteTooLong) {
                        return;
                    }

                    setIsConfirmOpen(true);
                }}
            >
                <label htmlFor="contact-status" className={LABEL_CLASS}>
                    ステータス
                </label>
                <select
                    id="contact-status"
                    value={data.status}
                    aria-invalid={errors.status !== undefined}
                    className={FIELD_CLASS}
                    onChange={(event) => setData('status', event.target.value)}
                >
                    {statusOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                {errors.status ? (
                    <p className="mt-1 text-[11.5px] font-bold text-admin-danger">
                        {errors.status}
                    </p>
                ) : null}

                <div className="mt-3 flex items-baseline gap-2">
                    <label htmlFor="contact-admin-note" className={LABEL_CLASS}>
                        対応メモ
                    </label>
                    <span
                        className={cn(
                            'font-mono text-[10.5px]',
                            isNoteTooLong
                                ? 'font-bold text-admin-danger'
                                : 'text-admin-ink-muted',
                        )}
                    >
                        {noteLength} / {ADMIN_NOTE_MAX}
                    </span>
                </div>
                <textarea
                    id="contact-admin-note"
                    rows={5}
                    value={data.admin_note}
                    aria-invalid={
                        isNoteTooLong || errors.admin_note !== undefined
                    }
                    className={FIELD_CLASS}
                    placeholder="対応の経緯や連絡内容を記録します（管理者のみ閲覧）"
                    onChange={(event) =>
                        setData('admin_note', event.target.value)
                    }
                />
                {isNoteTooLong ? (
                    <p className="mt-1 text-[11.5px] font-bold text-admin-danger">
                        対応メモは{ADMIN_NOTE_MAX}文字以内で入力してください
                    </p>
                ) : null}
                {errors.admin_note ? (
                    <p className="mt-1 text-[11.5px] font-bold text-admin-danger">
                        {errors.admin_note}
                    </p>
                ) : null}

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-3 w-full rounded-lg bg-admin-brand py-3 text-[13px] font-extrabold text-white disabled:opacity-60"
                >
                    対応状況を更新
                </button>
            </form>

            {handledAdminName ? (
                <p className="mt-2.5 text-[11.5px] leading-relaxed text-admin-ink-muted">
                    最終更新 {handledAt ?? '—'} ／ 管理者 {handledAdminName}
                </p>
            ) : null}

            <p className="mt-2.5 text-[11px] leading-relaxed text-admin-ink-muted">
                管理画面からの返信メール送信は行いません。返信はメールソフトから行い、記録は対応メモに残してください。
            </p>

            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="対応状況を更新しますか？"
                message={`${contactNumber} のステータスを「${selectedLabel}」に更新し、対応メモを記録します。お客様への通知メールは送信されません。`}
                confirmLabel="更新する"
                onConfirm={submit}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </section>
    );
}
