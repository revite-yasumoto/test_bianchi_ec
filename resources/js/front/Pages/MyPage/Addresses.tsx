import { router } from '@inertiajs/react';
import { useState } from 'react';
import { AddressModal } from '@/front/Components/Checkout/AddressModal';
import type { AddressData } from '@/front/Components/Checkout/AddressSelector';
import { AddressCard } from '@/front/Components/MyPage/AddressCard';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';
import { EmptyState } from '@/shared/Components/EmptyState';
import { Modal } from '@/shared/Components/Modal';

type Props = {
    addresses: AddressData[];
    prefectures: { id: number; name: string }[];
};

export default function Addresses({ addresses, prefectures }: Props) {
    const [editing, setEditing] = useState<AddressData | undefined>(undefined);
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [deleting, setDeleting] = useState<AddressData | null>(null);

    const openForm = (address?: AddressData) => {
        setEditing(address);
        setIsFormOpen(true);
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(route('addresses.destroy', [deleting.id]), {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
        });
    };

    return (
        <MyPageLayout
            title="配送先住所"
            description="お届け先の追加・編集・削除ができます。"
            heading="配送先住所"
        >
            {addresses.length === 0 ? (
                <EmptyState message="登録済みの配送先はありません。" />
            ) : (
                <ul className="flex flex-col gap-3.5">
                    {addresses.map((address) => (
                        <li key={address.id}>
                            <AddressCard
                                address={address}
                                onEdit={openForm}
                                onDelete={setDeleting}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <button
                type="button"
                onClick={() => openForm()}
                className="mt-3.5 w-full rounded-2xl border-[1.5px] border-dashed border-line py-3.5 text-[13px] font-bold text-brand"
            >
                ＋ 配送先を追加
            </button>

            <AddressModal
                // 編集対象を切り替えたときに入力欄の初期値を作り直す
                key={editing?.id ?? 'new'}
                isOpen={isFormOpen}
                address={editing}
                prefectures={prefectures}
                onClose={() => setIsFormOpen(false)}
            />

            <Modal
                isOpen={deleting !== null}
                title="配送先を削除します"
                onClose={() => setDeleting(null)}
            >
                <p className="text-[13px] leading-[1.9] text-ink2">
                    {deleting?.recipient_name}（{deleting?.label}
                    ）を削除します。確定済みのご注文のお届け先表示は変わりません。
                </p>

                <div className="mt-5 flex gap-2.5">
                    <button
                        type="button"
                        onClick={() => setDeleting(null)}
                        className="flex-1 rounded-full border border-line py-3 text-sm font-bold"
                    >
                        やめる
                    </button>
                    <button
                        type="button"
                        onClick={confirmDelete}
                        className="flex-1 rounded-full bg-coral py-3 text-sm font-bold text-white"
                    >
                        削除する
                    </button>
                </div>
            </Modal>
        </MyPageLayout>
    );
}
