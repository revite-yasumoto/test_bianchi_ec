import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { DataTable } from '@/admin/Components/DataTable';
import { Pagination } from '@/admin/Components/Pagination';
import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { NoticeEditorModal } from './NoticeEditorModal';

export type NoticeRow = {
    id: number;
    title: string;
    body: string;
    display_start_on: string;
    display_end_on: string;
    period_label: string;
    state: 'displaying' | 'scheduled' | 'ended';
    state_label: string;
    state_tone: Tone;
};

export type NoticeManagerProps = {
    notices: Paginated<NoticeRow>;
    /** 編集対象。`'new'` は新規作成、`null` はエディタを閉じた状態 */
    editing: NoticeRow | 'new' | null;
    onEdit: (target: NoticeRow | 'new') => void;
    onCloseEditor: () => void;
};

export function NoticeManager({
    notices,
    editing,
    onEdit,
    onCloseEditor,
}: NoticeManagerProps) {
    const { showToast } = useAdminToast();
    const [deleteTarget, setDeleteTarget] = useState<NoticeRow | null>(null);

    const handleSaved = () => {
        onCloseEditor();
        showToast('保存しました');
    };

    const handleDelete = () => {
        if (!deleteTarget) {
            return;
        }

        const { id } = deleteTarget;
        setDeleteTarget(null);

        router.delete(route('admin.notices.destroy', [id]), {
            preserveScroll: true,
            onSuccess: () => showToast('削除しました'),
        });
    };

    return (
        <>
            <DataTable
                rows={notices.data}
                rowKey={(row) => row.id}
                emptyMessage="重要なお知らせが登録されていません"
                columns={[
                    {
                        key: 'title',
                        header: 'タイトル',
                        className: 'font-semibold',
                        render: (row) => row.title,
                    },
                    {
                        key: 'period',
                        header: '掲載期間',
                        className:
                            'font-mono text-[11.5px] text-admin-ink-muted',
                        render: (row) => row.period_label,
                    },
                    {
                        key: 'state',
                        header: '状態',
                        render: (row) => (
                            <Badge tone={row.state_tone}>
                                {row.state_label}
                            </Badge>
                        ),
                    },
                    {
                        key: 'actions',
                        header: '',
                        className: 'text-right whitespace-nowrap',
                        render: (row) => (
                            <>
                                <button
                                    type="button"
                                    className="mr-3 text-[11.5px] font-bold text-admin-brand"
                                    onClick={() => onEdit(row)}
                                >
                                    編集
                                </button>
                                <button
                                    type="button"
                                    className="text-[11.5px] font-bold text-admin-danger"
                                    onClick={() => setDeleteTarget(row)}
                                >
                                    削除
                                </button>
                            </>
                        ),
                    },
                ]}
            />

            <p className="mt-3 text-[11.5px] text-admin-ink-muted">
                掲載期間内のお知らせがフロントTOPの上部にリンク形式で表示されます。
            </p>

            <Pagination links={notices.links} />

            {editing !== null ? (
                <NoticeEditorModal
                    key={editing === 'new' ? 'new' : editing.id}
                    row={editing === 'new' ? null : editing}
                    onClose={onCloseEditor}
                    onSaved={handleSaved}
                />
            ) : null}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="お知らせの削除"
                message="削除した内容は復元できません。フロント側の表示からも即時に削除されます。"
                confirmLabel="削除する"
                confirmVariant="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </>
    );
}
