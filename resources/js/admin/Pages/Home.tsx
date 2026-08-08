import { AdminLayout } from '@/admin/Layouts/AdminLayout';

export default function Home() {
    return (
        <AdminLayout title="ホーム">
            <div className="rounded-xl border border-admin-line bg-white p-8 text-sm text-admin-ink-muted">
                ログインしました。注文管理・ダッシュボード等の各機能は今後の単位で実装されます。
            </div>
        </AdminLayout>
    );
}
