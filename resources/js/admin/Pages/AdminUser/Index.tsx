import { Link } from '@inertiajs/react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { AdminUserTable } from '@/admin/Components/AdminUser/AdminUserTable';

export type AdminUserRow = {
    id: number;
    admin_code: string;
    name: string;
    email: string;
    registered_on: string;
};

type Props = { admins: AdminUserRow[] };

export default function Index({ admins }: Props) {
    return (
        <AdminLayout
            title="管理者マスタ"
            headerActions={
                <Link
                    href={route('admin.admins.create')}
                    className="rounded-lg bg-admin-brand px-4 py-2 text-[12.5px] font-bold text-white"
                >
                    ＋ 管理者登録
                </Link>
            }
        >
            <p className="mb-3.5 text-[11.5px] text-admin-ink-muted">
                管理者ごとの権限管理は行いません。登録された管理者はすべての機能を利用できます。
            </p>

            <AdminUserTable admins={admins} />
        </AdminLayout>
    );
}
