import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';
import { cn } from '@/lib/utils';
import { ConfirmDialog } from './ConfirmDialog';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';

export type MasterListRow = {
    id: number;
    name: string;
    /** 行の名称の右に添える補足（カテゴリの登録商品件数など） */
    note?: string;
};

type MasterListCardProps = {
    title?: string;
    description: string;
    rows: MasterListRow[];
    storeRouteName: string;
    /** 追加時に name と一緒に送る固定値（規格の type など） */
    storeParams?: Record<string, string>;
    destroyRouteName: string;
    placeholder: string;
    addedMessage: string;
    deletedMessage: string;
    /** 削除確認モーダルで対象名の後に添える注意文 */
    deleteNote: string;
    className?: string;
};

export function MasterListCard({
    title,
    description,
    rows,
    storeRouteName,
    storeParams,
    destroyRouteName,
    placeholder,
    addedMessage,
    deletedMessage,
    deleteNote,
    className,
}: MasterListCardProps) {
    const { showToast } = useAdminToast();
    const [pendingRow, setPendingRow] = useState<MasterListRow | null>(null);
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<Record<string, string>>({ name: '', ...storeParams });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route(storeRouteName), {
            preserveScroll: true,
            onSuccess: () => {
                reset('name');
                showToast(addedMessage);
            },
        });
    };

    const handleDelete = (row: MasterListRow) => {
        setPendingRow(null);

        router.delete(route(destroyRouteName, [row.id]), {
            preserveScroll: true,
            onSuccess: () => showToast(deletedMessage),
            onError: (deleteErrors) =>
                showToast(deleteErrors.delete ?? '削除できませんでした。'),
        });
    };

    return (
        <div
            className={cn(
                'rounded-xl border border-admin-line bg-white p-5',
                className,
            )}
        >
            {title ? (
                <h2 className="mb-1.5 text-[13px] font-extrabold text-admin-ink">
                    {title}
                </h2>
            ) : null}
            <p className="mb-3.5 text-[11.5px] text-admin-ink-muted">
                {description}
            </p>

            <ul className="flex flex-col">
                {rows.map((row) => (
                    <li
                        key={row.id}
                        className="flex items-center border-b border-admin-line py-2.5 text-[13px] font-semibold text-admin-ink"
                    >
                        {row.name}
                        {row.note ? (
                            <span className="ml-3.5 text-[11.5px] font-normal text-admin-ink-muted">
                                {row.note}
                            </span>
                        ) : null}
                        <button
                            type="button"
                            className="ml-auto text-[11.5px] font-bold text-admin-danger"
                            onClick={() => setPendingRow(row)}
                        >
                            削除
                        </button>
                    </li>
                ))}
            </ul>

            <form onSubmit={handleSubmit} className="mt-3.5">
                <div className="flex gap-2">
                    <label
                        htmlFor={`${storeRouteName}-name`}
                        className="sr-only"
                    >
                        {title ? `${title}を追加` : '追加する名称'}
                    </label>
                    <input
                        id={`${storeRouteName}-name`}
                        type="text"
                        value={data.name}
                        placeholder={placeholder}
                        aria-invalid={errors.name ? true : undefined}
                        onChange={(event) => {
                            clearErrors('name');
                            setData('name', event.target.value);
                        }}
                        className="w-full rounded-lg border border-admin-line px-3 py-2 text-base"
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-admin-brand px-5 py-2 text-[12.5px] font-bold whitespace-nowrap text-white disabled:opacity-60"
                    >
                        追加
                    </button>
                </div>
                {errors.name ? (
                    <p className="mt-1.5 text-[11.5px] font-bold text-admin-danger">
                        {errors.name}
                    </p>
                ) : null}
            </form>

            <ConfirmDialog
                isOpen={pendingRow !== null}
                title="削除の確認"
                message={
                    pendingRow
                        ? `「${pendingRow.name}」を削除します。${deleteNote}`
                        : ''
                }
                confirmLabel="削除する"
                confirmVariant="danger"
                onConfirm={() => pendingRow && handleDelete(pendingRow)}
                onCancel={() => setPendingRow(null)}
            />
        </div>
    );
}
