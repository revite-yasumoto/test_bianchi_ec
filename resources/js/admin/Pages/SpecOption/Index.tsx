import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { MasterListCard } from '@/admin/Components/MasterListCard';
import { SpecOptionType } from '@/shared/lib/enums';

type SpecOptionRow = { id: number; name: string };

type Props = { sizes: SpecOptionRow[]; colors: SpecOptionRow[] };

const DELETE_NOTE = '既に登録済みの商品のSKUには影響しません。';

export default function Index({ sizes, colors }: Props) {
    return (
        <AdminLayout title="規格管理">
            <div className="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-4">
                <MasterListCard
                    title="サイズ"
                    description="商品登録のバリエーション設定でワンクリック選択できます。"
                    rows={sizes}
                    storeRouteName="admin.spec-options.store"
                    storeParams={{ type: SpecOptionType.Size }}
                    destroyRouteName="admin.spec-options.destroy"
                    placeholder="例：XXL"
                    addedMessage="サイズを追加しました"
                    deletedMessage="サイズを削除しました"
                    deleteNote={DELETE_NOTE}
                />
                <MasterListCard
                    title="カラー"
                    description="登録済みのカラーはSKUの組み合わせ生成に使用されます。"
                    rows={colors}
                    storeRouteName="admin.spec-options.store"
                    storeParams={{ type: SpecOptionType.Color }}
                    destroyRouteName="admin.spec-options.destroy"
                    placeholder="例：ネイビー"
                    addedMessage="カラーを追加しました"
                    deletedMessage="カラーを削除しました"
                    deleteNote={DELETE_NOTE}
                />
            </div>
        </AdminLayout>
    );
}
