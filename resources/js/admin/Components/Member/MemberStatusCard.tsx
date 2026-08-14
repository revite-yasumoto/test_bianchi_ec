import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { UserStatus } from '@/shared/lib/enums';

type MemberStatusCardProps = {
    memberId: number;
    memberName: string;
    status: string;
};

const STATUS_LABEL: Record<string, string> = {
    [UserStatus.Active]: '有効',
    [UserStatus.Suspended]: '休会',
    [UserStatus.Withdrawn]: '退会',
};

export function MemberStatusCard({
    memberId,
    memberName,
    status,
}: MemberStatusCardProps) {
    const { showToast } = useAdminToast();
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const isWithdrawn = status === UserStatus.Withdrawn;
    const nextStatus =
        status === UserStatus.Active ? UserStatus.Suspended : UserStatus.Active;

    const submit = () => {
        setIsConfirmOpen(false);

        router.put(
            route('admin.members.status.update', [memberId]),
            { status: nextStatus },
            {
                preserveScroll: true,
                onSuccess: () => showToast('会員ステータスを更新しました'),
            },
        );
    };

    return (
        <div className="rounded-xl border border-admin-line bg-white p-5">
            <h2 className="mb-2.5 text-[13px] font-extrabold text-admin-ink">
                ステータス更新
            </h2>
            <p className="text-[12.5px] leading-relaxed text-admin-ink-muted">
                現在のステータスは「{STATUS_LABEL[status]}」です。
                {isWithdrawn
                    ? '退会は会員ご本人の操作によるもので、管理画面から戻すことはできません。'
                    : status === UserStatus.Active
                      ? '休会にすると、この会員はログインできなくなります。'
                      : '有効に戻すと、この会員は再びログインできるようになります。'}
            </p>

            {isWithdrawn ? null : (
                <button
                    type="button"
                    className="mt-3 w-full rounded-lg bg-admin-brand py-3 text-[13px] font-extrabold text-white"
                    onClick={() => setIsConfirmOpen(true)}
                >
                    「{STATUS_LABEL[nextStatus]}」に変更する
                </button>
            )}

            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="会員ステータスの更新"
                message={`${memberName} のステータスを「${STATUS_LABEL[nextStatus]}」に変更します。`}
                confirmLabel="変更する"
                confirmVariant={
                    nextStatus === UserStatus.Suspended ? 'danger' : 'default'
                }
                onConfirm={submit}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </div>
    );
}
