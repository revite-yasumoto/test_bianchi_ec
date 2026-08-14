import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { DataTable } from '@/admin/Components/DataTable';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import type { AdminUserRow } from '@/admin/Pages/AdminUser/Index';

type AdminUserTableProps = { admins: AdminUserRow[] };

export function AdminUserTable({ admins }: AdminUserTableProps) {
    const { auth } = usePage<AdminSharedProps>().props;
    const { showToast } = useAdminToast();
    const [pendingRow, setPendingRow] = useState<AdminUserRow | null>(null);

    const handleDelete = (row: AdminUserRow) => {
        setPendingRow(null);

        router.delete(route('admin.admins.destroy', [row.id]), {
            preserveScroll: true,
            onSuccess: () => showToast('管理者を削除しました'),
            onError: (errors) =>
                showToast(errors.delete ?? '削除できませんでした。'),
        });
    };

    return (
        <>
            <DataTable
                columns={[
                    {
                        key: 'admin_code',
                        header: '管理者ID',
                        render: (row) => (
                            <span className="font-mono text-[11.5px]">
                                {row.admin_code}
                            </span>
                        ),
                    },
                    {
                        key: 'name',
                        header: '氏名',
                        render: (row) => (
                            <span className="font-bold">
                                {row.name}
                                {row.email === auth.admin?.email ? (
                                    <span className="ml-2 text-[11px] font-normal text-admin-ink-muted">
                                        （ログイン中）
                                    </span>
                                ) : null}
                            </span>
                        ),
                    },
                    {
                        key: 'email',
                        header: 'メールアドレス',
                        render: (row) => (
                            <span className="text-admin-ink-muted">
                                {row.email}
                            </span>
                        ),
                    },
                    {
                        key: 'registered_on',
                        header: '登録日',
                        render: (row) => (
                            <span className="text-admin-ink-muted">
                                {row.registered_on}
                            </span>
                        ),
                    },
                    {
                        key: 'actions',
                        header: '',
                        className: 'text-right',
                        render: (row) => (
                            <span className="flex justify-end gap-3">
                                <Link
                                    href={route('admin.admins.edit', [row.id])}
                                    className="text-[11.5px] font-bold text-admin-brand"
                                >
                                    編集
                                </Link>
                                <button
                                    type="button"
                                    className="text-[11.5px] font-bold text-admin-danger"
                                    onClick={() => setPendingRow(row)}
                                >
                                    削除
                                </button>
                            </span>
                        ),
                    },
                ]}
                rows={admins}
                rowKey={(row) => row.id}
                emptyMessage="管理者が登録されていません"
            />

            <ConfirmDialog
                isOpen={pendingRow !== null}
                title="管理者の削除"
                message={
                    pendingRow
                        ? `「${pendingRow.name}（${pendingRow.admin_code}）」を削除します。この管理者は管理画面にログインできなくなります。`
                        : ''
                }
                confirmLabel="削除する"
                confirmVariant="danger"
                onConfirm={() => pendingRow && handleDelete(pendingRow)}
                onCancel={() => setPendingRow(null)}
            />
        </>
    );
}
