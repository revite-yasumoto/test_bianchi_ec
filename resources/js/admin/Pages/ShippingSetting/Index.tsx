import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import {
    ShippingSettingEditor,
    type ShippingSettingEditorProps,
} from '@/admin/Components/ShippingSetting/ShippingSettingEditor';

/**
 * 編集本体は `AdminLayout` のトーストContextを使うため、レイアウトの子として描画する。
 */
export default function Index(props: ShippingSettingEditorProps) {
    return (
        <AdminLayout title="送料設定マスタ">
            <ShippingSettingEditor {...props} />
        </AdminLayout>
    );
}
