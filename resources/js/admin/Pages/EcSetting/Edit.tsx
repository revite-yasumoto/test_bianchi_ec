import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import {
    EcSettingForm,
    type EcSettingFormProps,
} from '@/admin/Components/EcSetting/EcSettingForm';

/**
 * フォーム本体は `AdminLayout` のトーストContextを使うため、レイアウトの子として描画する。
 */
export default function Edit(props: EcSettingFormProps) {
    return (
        <AdminLayout title="EC基本設定">
            <EcSettingForm {...props} />
        </AdminLayout>
    );
}
