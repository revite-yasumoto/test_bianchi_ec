import { useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { NewsManager, type NewsRow } from '@/admin/Components/News/NewsManager';

type Props = {
    news: Paginated<NewsRow>;
    categoryOptions: string[];
};

/**
 * エディタの開閉はヘッダーの「新規作成」と一覧の「編集」の両方から操作するため、ページ側で持つ。
 */
export default function Index({ news, categoryOptions }: Props) {
    const [editing, setEditing] = useState<NewsRow | 'new' | null>(null);

    return (
        <AdminLayout
            title="新着ニュース管理"
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
            <NewsManager
                news={news}
                categoryOptions={categoryOptions}
                editing={editing}
                onEdit={setEditing}
                onCloseEditor={() => setEditing(null)}
            />
        </AdminLayout>
    );
}
