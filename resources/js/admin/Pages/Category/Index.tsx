import { useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { MasterListCard } from '@/admin/Components/MasterListCard';
import {
    CategoryImageModal,
    type CategoryImageTarget,
} from '@/admin/Components/Category/CategoryImageModal';

type CategoryRow = {
    id: number;
    name: string;
    product_count: number;
    image_url: string | null;
};

type Props = { categories: CategoryRow[] };

export default function Index({ categories }: Props) {
    const [imageTarget, setImageTarget] = useState<CategoryImageTarget | null>(
        null,
    );
    const imageUrls = new Map(
        categories.map((category) => [category.id, category.image_url]),
    );
    const rows = categories.map((category) => ({
        id: category.id,
        name: category.name,
        note: `${category.product_count}商品`,
    }));

    return (
        <AdminLayout title="カテゴリ管理">
            <MasterListCard
                description="登録したカテゴリは商品登録の選択肢・商品一覧／在庫の絞り込みに反映されます。"
                rows={rows}
                storeRouteName="admin.categories.store"
                destroyRouteName="admin.categories.destroy"
                placeholder="例：ヘルメット"
                addedMessage="カテゴリを追加しました"
                deletedMessage="カテゴリを削除しました"
                deleteNote="商品が登録されているカテゴリは削除できません。"
                renderLeading={(row) => {
                    const imageUrl = imageUrls.get(row.id);

                    return (
                        <span className="mr-3 block h-9 w-12 shrink-0 overflow-hidden rounded border border-admin-line bg-admin-bg">
                            {imageUrl ? (
                                <img
                                    src={imageUrl}
                                    alt="登録済みの画像"
                                    width={48}
                                    height={36}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <span className="sr-only">画像未登録</span>
                            )}
                        </span>
                    );
                }}
                renderActions={(row) => (
                    <button
                        type="button"
                        className="text-sm font-bold text-admin-brand"
                        onClick={() =>
                            setImageTarget({
                                id: row.id,
                                name: row.name,
                                image_url: imageUrls.get(row.id) ?? null,
                            })
                        }
                    >
                        画像
                    </button>
                )}
                className="max-w-[560px]"
            />

            {imageTarget ? (
                <CategoryImageModal
                    key={imageTarget.id}
                    category={imageTarget}
                    onClose={() => setImageTarget(null)}
                />
            ) : null}
        </AdminLayout>
    );
}
