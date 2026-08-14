import { Link, usePage } from '@inertiajs/react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { AdminUserTable } from '@/admin/Components/AdminUser/AdminUserTable';
import { CsvActions } from '@/admin/Components/Csv/CsvActions';
import { ImportResultPanel } from '@/admin/Components/Csv/ImportResultPanel';

export type AdminUserRow = {
    id: number;
    admin_code: string;
    name: string;
    email: string;
    registered_on: string;
};

type Props = { admins: AdminUserRow[] };

export default function Index({ admins }: Props) {
    const { flash } = usePage<AdminSharedProps>().props;

    return (
        <AdminLayout
            title="管理者マスタ"
            headerActions={
                <div className="flex items-center gap-2">
                    <CsvActions
                        exportUrl={route('admin.admins.csv.export')}
                        importUrl={route('admin.admins.csv.import')}
                        targetLabel="管理者データ"
                    />
                    <Link
                        href={route('admin.admins.create')}
                        className="rounded-lg bg-admin-brand px-4 py-2 text-[12.5px] font-bold whitespace-nowrap text-white"
                    >
                        ＋ 管理者登録
                    </Link>
                </div>
            }
        >
            <p className="mb-3.5 text-[11.5px] text-admin-ink-muted">
                管理者ごとの権限管理は行いません。登録された管理者はすべての機能を利用できます。
            </p>

            <ImportResultPanel result={flash.importResult} className="mb-3.5" />

            <AdminUserTable admins={admins} />
        </AdminLayout>
    );
}
