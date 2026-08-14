import { useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import {
    NoticeManager,
    type NoticeRow,
} from '@/admin/Components/Notice/NoticeManager';

type Props = {
    notices: Paginated<NoticeRow>;
};

/**
 * エディタの開閉はヘッダーの「新規作成」と一覧の「編集」の両方から操作するため、ページ側で持つ。
 */
export default function Index({ notices }: Props) {
    const [editing, setEditing] = useState<NoticeRow | 'new' | null>(null);

    return (
        <AdminLayout
            title="重要なお知らせ管理"
            headerActions={
                <button
                    type="button"
                    className="rounded-lg bg-admin-brand px-4 py-2 text-[12.5px] font-bold whitespace-nowrap text-white"
                    onClick={() => setEditing('new')}
                >
                    ＋ 新規作成
                </button>
            }
        >
            <NoticeManager
                notices={notices}
                editing={editing}
                onEdit={setEditing}
                onCloseEditor={() => setEditing(null)}
            />
        </AdminLayout>
    );
}
