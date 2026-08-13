import {
    addressLines,
    type AddressData,
} from '@/front/Components/Checkout/AddressSelector';

type AddressCardProps = {
    address: AddressData;
    onEdit: (address: AddressData) => void;
    onDelete: (address: AddressData) => void;
};

export function AddressCard({ address, onEdit, onDelete }: AddressCardProps) {
    return (
        <article className="rounded-2xl border border-line p-5">
            <h3 className="text-[13.5px] font-bold">
                {address.recipient_name}（{address.label}）
                {address.is_default ? (
                    <span className="ml-2 rounded-full bg-bg2 px-2 py-0.5 text-[11px] font-bold text-ink2">
                        既定
                    </span>
                ) : null}
            </h3>
            <p className="mt-1.5 text-[12.5px] leading-[1.7] text-ink2">
                {addressLines(address)}
            </p>

            <div className="mt-3.5 flex gap-2.5">
                <button
                    type="button"
                    onClick={() => onEdit(address)}
                    className="rounded-full border border-line px-4 py-2 text-xs font-bold"
                >
                    編集
                </button>
                <button
                    type="button"
                    onClick={() => onDelete(address)}
                    className="rounded-full border border-line px-4 py-2 text-xs font-bold text-coral"
                >
                    削除
                </button>
            </div>
        </article>
    );
}
