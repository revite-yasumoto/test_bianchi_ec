import { useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import {
    BannerManager,
    type BannerOption,
    type BannerRow,
} from '@/admin/Components/Banner/BannerManager';

type Props = {
    banners: BannerRow[];
    backgroundOptions: { value: string; label: string }[];
    productOptions: BannerOption[];
};

/**
 * エディタの開閉はヘッダーの「新規作成」と一覧の「編集」の両方から操作するため、ページ側で持つ。
 */
export default function Index({
    banners,
    backgroundOptions,
    productOptions,
}: Props) {
    const [editing, setEditing] = useState<BannerRow | 'new' | null>(null);

    return (
        <AdminLayout
            title="メインビジュアル管理"
            headerActions={
                <button
                    type="button"
                    className="rounded-lg bg-admin-brand px-4 py-2 text-[15px] font-bold whitespace-nowrap text-white"
                    onClick={() => setEditing('new')}
                >
                    ＋ 新規作成
                </button>
            }
        >
            <BannerManager
                banners={banners}
                backgroundOptions={backgroundOptions}
                productOptions={productOptions}
                editing={editing}
                onEdit={setEditing}
                onCloseEditor={() => setEditing(null)}
            />
        </AdminLayout>
    );
}
