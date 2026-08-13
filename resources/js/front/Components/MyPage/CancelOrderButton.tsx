import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Modal } from '@/shared/Components/Modal';

type CancelOrderButtonProps = {
    orderId: number;
    orderNumber: string;
    className?: string;
};

export function CancelOrderButton({
    orderId,
    orderNumber,
    className,
}: CancelOrderButtonProps) {
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const { post, processing } = useForm({});

    const submit = () => {
        post(route('mypage.orders.cancel', [orderId]), {
            preserveScroll: true,
            onSuccess: () => setIsConfirmOpen(false),
        });
    };

    return (
        <>
            <button
                type="button"
                onClick={() => setIsConfirmOpen(true)}
                className={cn(
                    'rounded-full border border-line px-4 py-2 text-xs font-bold text-ink2',
                    className,
                )}
            >
                キャンセル依頼
            </button>

            <Modal
                isOpen={isConfirmOpen}
                title="注文をキャンセルします"
                onClose={() => setIsConfirmOpen(false)}
            >
                <p className="text-[13px] leading-[1.9] text-ink2">
                    注文
                    <span className="mx-1 font-mono font-bold text-ink">
                        {orderNumber}
                    </span>
                    をキャンセルします。キャンセルした注文は元に戻せません。
                </p>

                <div className="mt-5 flex gap-2.5">
                    <button
                        type="button"
                        onClick={() => setIsConfirmOpen(false)}
                        className="flex-1 rounded-full border border-line py-3 text-sm font-bold"
                    >
                        やめる
                    </button>
                    <button
                        type="button"
                        onClick={submit}
                        disabled={processing}
                        className="flex-1 rounded-full bg-coral py-3 text-sm font-bold text-white disabled:opacity-60"
                    >
                        キャンセルする
                    </button>
                </div>
            </Modal>
        </>
    );
}
