import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { DataTable } from '@/admin/Components/DataTable';
import { Pagination } from '@/admin/Components/Pagination';
import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { NewsEditorModal } from './NewsEditorModal';

export type NewsRow = {
    id: number;
    published_on: string;
    published_on_input: string;
    category: string;
    category_tone: Tone;
    title: string;
    body: string;
    is_published: boolean;
    state_label: string;
};

export type NewsManagerProps = {
    news: Paginated<NewsRow>;
    categoryOptions: string[];
    /** 編集対象。`'new'` は新規作成、`null` はエディタを閉じた状態 */
    editing: NewsRow | 'new' | null;
    onEdit: (target: NewsRow | 'new') => void;
    onCloseEditor: () => void;
};

export function NewsManager({
    news,
    categoryOptions,
    editing,
    onEdit,
    onCloseEditor,
}: NewsManagerProps) {
    const { showToast } = useAdminToast();
    const [deleteTarget, setDeleteTarget] = useState<NewsRow | null>(null);

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

        router.delete(route('admin.news.destroy', [id]), {
            preserveScroll: true,
            onSuccess: () => showToast('削除しました'),
        });
    };

    return (
        <>
            <DataTable
                rows={news.data}
                rowKey={(row) => row.id}
                emptyMessage="新着ニュースが登録されていません"
                columns={[
                    {
                        key: 'published_on',
                        header: '掲載日',
                        className:
                            'font-mono text-[11.5px] text-admin-ink-muted',
                        render: (row) => row.published_on,
                    },
                    {
                        key: 'category',
                        header: '種別',
                        render: (row) => (
                            <Badge tone={row.category_tone}>
                                {row.category}
                            </Badge>
                        ),
                    },
                    {
                        key: 'title',
                        header: 'タイトル',
                        className: 'font-semibold',
                        render: (row) => row.title,
                    },
                    {
                        key: 'state',
                        header: '状態',
                        className: 'text-admin-ink-muted',
                        render: (row) => row.state_label,
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

            <Pagination links={news.links} />

            {editing !== null ? (
                <NewsEditorModal
                    key={editing === 'new' ? 'new' : editing.id}
                    row={editing === 'new' ? null : editing}
                    categoryOptions={categoryOptions}
                    onClose={onCloseEditor}
                    onSaved={handleSaved}
                />
            ) : null}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="ニュースの削除"
                message="削除した内容は復元できません。フロント側の表示からも即時に削除されます。"
                confirmLabel="削除する"
                confirmVariant="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </>
    );
}
