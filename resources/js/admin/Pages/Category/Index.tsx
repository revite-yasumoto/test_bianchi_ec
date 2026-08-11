import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { MasterListCard } from '@/admin/Components/MasterListCard';

type CategoryRow = { id: number; name: string; product_count: number };

type Props = { categories: CategoryRow[] };

export default function Index({ categories }: Props) {
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
                className="max-w-[560px]"
            />
        </AdminLayout>
    );
}
