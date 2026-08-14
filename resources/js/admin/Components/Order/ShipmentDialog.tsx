import { useState } from 'react';
import { Modal } from '@/shared/Components/Modal';

type ShipmentDialogProps = {
    isOpen: boolean;
    onConfirm: (trackingNumber: string, notifiesCustomer: boolean) => void;
    onCancel: () => void;
};

export function ShipmentDialog({
    isOpen,
    onConfirm,
    onCancel,
}: ShipmentDialogProps) {
    const [trackingNumber, setTrackingNumber] = useState('');
    const [notifiesCustomer, setNotifiesCustomer] = useState(true);

    // Modal は閉じている間も本コンポーネント自体は残るため、閉じる操作で入力を戻す
    const close = () => {
        setTrackingNumber('');
        setNotifiesCustomer(true);
        onCancel();
    };

    return (
        <Modal isOpen={isOpen} title="出荷済みへの変更" onClose={close}>
            <p className="text-[12.5px] leading-relaxed text-admin-ink-muted">
                送り状番号を控える場合は入力してください。未入力のままでも変更できます。
            </p>

            <label
                htmlFor="tracking_number"
                className="mt-4 block text-[12px] font-bold text-admin-ink"
            >
                送り状番号（任意）
            </label>
            <input
                id="tracking_number"
                type="text"
                maxLength={50}
                value={trackingNumber}
                className="mt-1.5 w-full rounded-lg border border-admin-line px-3 py-2 text-base"
                onChange={(event) => setTrackingNumber(event.target.value)}
            />

            <label
                htmlFor="notifies_customer"
                className="mt-4 flex items-center gap-2 text-[12.5px] text-admin-ink"
            >
                <input
                    id="notifies_customer"
                    type="checkbox"
                    checked={notifiesCustomer}
                    className="h-4 w-4 rounded border-admin-line"
                    onChange={(event) =>
                        setNotifiesCustomer(event.target.checked)
                    }
                />
                出荷完了メールをお客様へ送る
            </label>

            <div className="mt-6 flex justify-end gap-2.5">
                <button
                    type="button"
                    className="rounded-lg border border-admin-line px-4 py-2 text-[12.5px] font-bold"
                    onClick={close}
                >
                    キャンセル
                </button>
                <button
                    type="button"
                    className="rounded-lg bg-admin-brand px-4 py-2 text-[12.5px] font-extrabold text-white"
                    onClick={() => onConfirm(trackingNumber, notifiesCustomer)}
                >
                    更新する
                </button>
            </div>
        </Modal>
    );
}
