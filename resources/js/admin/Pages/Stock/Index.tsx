import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import {
    StockManager,
    type StockManagerProps,
} from '@/admin/Components/Stock/StockManager';

/**
 * 一覧本体は `AdminLayout` のトーストContextを使うため、レイアウトの子として描画する。
 */
export default function Index(props: StockManagerProps) {
    return (
        <AdminLayout title="在庫">
            <StockManager {...props} />
        </AdminLayout>
    );
}
