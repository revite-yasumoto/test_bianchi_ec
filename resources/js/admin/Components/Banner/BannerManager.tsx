import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { DataTable } from '@/admin/Components/DataTable';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { BannerEditorModal } from './BannerEditorModal';

export type BannerRow = {
    id: number;
    tag: string;
    title: string;
    subtitle: string | null;
    /** BannerBackground の区分コード（選択肢の値） */
    background: string;
    /** 一覧の色見本に使う CSS の背景値 */
    background_gradient: string;
    product_id: number | null;
    link_url: string | null;
    sort_order: number;
    is_active: boolean;
    state_label: string;
    pc_image_url: string | null;
    sp_image_url: string | null;
};

export type BannerOption = { id: number; label: string };

export type BannerManagerProps = {
    banners: BannerRow[];
    backgroundOptions: { value: string; label: string }[];
    productOptions: BannerOption[];
    /** 編集対象。`'new'` は新規作成、`null` はエディタを閉じた状態 */
    editing: BannerRow | 'new' | null;
    onEdit: (target: BannerRow | 'new') => void;
    onCloseEditor: () => void;
};

export function BannerManager({
    banners,
    backgroundOptions,
    productOptions,
    editing,
    onEdit,
    onCloseEditor,
}: BannerManagerProps) {
    const { showToast } = useAdminToast();
    const [deleteTarget, setDeleteTarget] = useState<BannerRow | null>(null);

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

        router.delete(route('admin.banners.destroy', [id]), {
            preserveScroll: true,
            onSuccess: () => showToast('削除しました'),
        });
    };

    return (
        <>
            <DataTable
                rows={banners}
                rowKey={(row) => row.id}
                emptyMessage="メインビジュアルが登録されていません"
                columns={[
                    {
                        key: 'image',
                        header: '画像',
                        render: (row) => {
                            const thumbnailUrl =
                                row.pc_image_url ?? row.sp_image_url;

                            return thumbnailUrl ? (
                                <img
                                    src={thumbnailUrl}
                                    alt="登録済みの画像"
                                    width={84}
                                    height={36}
                                    className="h-9 w-21 rounded object-cover"
                                />
                            ) : (
                                <span
                                    className="block h-9 w-21 rounded"
                                    style={{
                                        backgroundImage:
                                            row.background_gradient,
                                    }}
                                >
                                    <span className="sr-only">画像未登録</span>
                                </span>
                            );
                        },
                    },
                    {
                        key: 'title',
                        header: '小ラベル / 見出し',
                        render: (row) => (
                            <>
                                <span className="block font-mono text-sm text-admin-ink-muted">
                                    {row.tag}
                                </span>
                                <span className="block font-semibold">
                                    {row.title.replace(/\n/g, ' ')}
                                </span>
                            </>
                        ),
                    },
                    {
                        key: 'sort_order',
                        header: '表示順',
                        className: 'font-mono text-sm',
                        render: (row) => row.sort_order,
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
                                    className="mr-3 text-sm font-bold text-admin-brand"
                                    onClick={() => onEdit(row)}
                                >
                                    編集
                                </button>
                                <button
                                    type="button"
                                    className="text-sm font-bold text-admin-danger"
                                    onClick={() => setDeleteTarget(row)}
                                >
                                    削除
                                </button>
                            </>
                        ),
                    },
                ]}
            />

            {editing !== null ? (
                <BannerEditorModal
                    key={editing === 'new' ? 'new' : editing.id}
                    row={editing === 'new' ? null : editing}
                    backgroundOptions={backgroundOptions}
                    productOptions={productOptions}
                    onClose={onCloseEditor}
                    onSaved={handleSaved}
                />
            ) : null}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="メインビジュアルの削除"
                message="削除した内容と画像は復元できません。TOPページの表示からも即時に削除されます。"
                confirmLabel="削除する"
                confirmVariant="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </>
    );
}
