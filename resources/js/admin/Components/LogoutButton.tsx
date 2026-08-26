import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from './ConfirmDialog';

export function LogoutButton() {
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    return (
        <>
            <button
                type="button"
                className="rounded-lg border border-admin-line bg-white px-3.5 py-2 text-[15px] font-bold whitespace-nowrap text-admin-ink"
                onClick={() => setIsConfirmOpen(true)}
            >
                ログアウト
            </button>
            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="ログアウトしますか？"
                message="編集中の内容は保存されません。再度ログインするには管理者IDとパスワードが必要です。"
                confirmLabel="ログアウト"
                confirmVariant="danger"
                onConfirm={() => router.post(route('admin.logout'))}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </>
    );
}
