import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { ShipmentDialog } from '@/admin/Components/Order/ShipmentDialog';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { OrderStatus } from '@/shared/lib/enums';

type StatusOption = { value: string; label: string };

type StatusUpdateCardProps = {
    orderId: number;
    currentStatusLabel: string;
    statusOptions: StatusOption[];
    error?: string;
};

export function StatusUpdateCard({
    orderId,
    currentStatusLabel,
    statusOptions,
    error,
}: StatusUpdateCardProps) {
    const { showToast } = useAdminToast();
    const [selected, setSelected] = useState(statusOptions[0]?.value ?? '');
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const [isShipmentOpen, setIsShipmentOpen] = useState(false);

    const selectedLabel =
        statusOptions.find((option) => option.value === selected)?.label ?? '';
    const isShipping = selected === OrderStatus.Shipped;

    const submit = (trackingNumber = '', notifiesCustomer = false) => {
        setIsConfirmOpen(false);
        setIsShipmentOpen(false);

        router.put(
            route('admin.orders.status.update', [orderId]),
            {
                status: selected,
                tracking_number: trackingNumber,
                notifies_customer: notifiesCustomer,
            },
            {
                preserveScroll: true,
                onSuccess: () => showToast('ステータスを更新しました'),
            },
        );
    };

    return (
        <div className="rounded-xl border border-admin-line bg-white p-5">
            <h2 className="mb-2.5 text-[13px] font-extrabold text-admin-ink">
                ステータス更新
            </h2>

            {statusOptions.length === 0 ? (
                <p className="text-[12.5px] leading-relaxed text-admin-ink-muted">
                    「{currentStatusLabel}
                    」は最終ステータスのため、これ以上の変更はできません。
                </p>
            ) : (
                <>
                    <label htmlFor="status" className="sr-only">
                        変更後のステータス
                    </label>
                    <select
                        id="status"
                        value={selected}
                        className="w-full rounded-lg border border-admin-line px-3 py-2 text-base"
                        onChange={(event) => setSelected(event.target.value)}
                    >
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    <button
                        type="button"
                        className="mt-3 w-full rounded-lg bg-admin-brand py-3 text-[13px] font-extrabold text-white"
                        onClick={() =>
                            isShipping
                                ? setIsShipmentOpen(true)
                                : setIsConfirmOpen(true)
                        }
                    >
                        ステータスを更新
                    </button>

                    {error ? (
                        <p className="mt-2 text-[11.5px] font-bold text-admin-danger">
                            {error}
                        </p>
                    ) : null}

                    <p className="mt-2.5 text-[11.5px] leading-relaxed text-admin-ink-muted">
                        {isShipping
                            ? '更新時に送り状番号の入力とメール送信の確認が表示されます。'
                            : '更新時は確認ダイアログが表示されます。'}
                    </p>
                </>
            )}

            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="ステータスの更新"
                message={`ステータスを「${currentStatusLabel}」から「${selectedLabel}」に変更します。この操作は取り消せません。`}
                confirmLabel="更新する"
                onConfirm={() => submit()}
                onCancel={() => setIsConfirmOpen(false)}
            />

            <ShipmentDialog
                isOpen={isShipmentOpen}
                onConfirm={submit}
                onCancel={() => setIsShipmentOpen(false)}
            />
        </div>
    );
}
