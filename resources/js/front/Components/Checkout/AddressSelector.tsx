import { cn } from '@/lib/utils';

export type AddressData = {
    id: number;
    label: string;
    recipient_name: string;
    postal_code: string;
    prefecture_id: number;
    prefecture_name: string;
    city: string;
    address_line1: string;
    address_line2: string | null;
    tel: string;
    is_default: boolean;
};

/** カード内に1行で出す住所（〒・都道府県から番地・建物名・電話番号まで） */
export function addressLines(address: AddressData): string {
    const building = address.address_line2 ? ` ${address.address_line2}` : '';

    return `〒${address.postal_code} ${address.prefecture_name}${address.city}${address.address_line1}${building} / ${address.tel}`;
}

type AddressSelectorProps = {
    addresses: AddressData[];
    selectedId: number | null;
    error?: string;
    onChange: (addressId: number) => void;
    onAddNew: () => void;
};

export function AddressSelector({
    addresses,
    selectedId,
    error,
    onChange,
    onAddNew,
}: AddressSelectorProps) {
    return (
        <fieldset>
            <legend className="mb-3 text-[15px] font-extrabold">
                お届け先
            </legend>

            <div className="flex flex-col gap-2.5">
                {addresses.map((address) => {
                    const isSelected = address.id === selectedId;

                    return (
                        <label
                            key={address.id}
                            className={cn(
                                'flex cursor-pointer gap-3 rounded-2xl border-[1.5px] px-4.5 py-4',
                                isSelected
                                    ? 'border-brand bg-[#F3F8FA]'
                                    : 'border-line bg-white',
                            )}
                        >
                            <input
                                type="radio"
                                name="address_id"
                                value={address.id}
                                checked={isSelected}
                                onChange={() => onChange(address.id)}
                                className="mt-0.5 h-4.5 w-4.5 shrink-0 accent-brand"
                            />
                            <span>
                                <span className="block text-[13.5px] font-bold">
                                    {address.recipient_name}（{address.label}）
                                    {address.is_default ? (
                                        <span className="ml-2 rounded-full bg-bg2 px-2 py-0.5 text-[11px] font-bold text-ink2">
                                            既定
                                        </span>
                                    ) : null}
                                </span>
                                <span className="mt-1 block text-[12.5px] leading-[1.6] text-ink2">
                                    {addressLines(address)}
                                </span>
                            </span>
                        </label>
                    );
                })}

                <button
                    type="button"
                    onClick={onAddNew}
                    className="rounded-2xl border-[1.5px] border-dashed border-line py-3.5 text-[13px] font-bold text-brand"
                >
                    ＋ 新しいお届け先を追加
                </button>
            </div>

            {error ? (
                <p
                    role="alert"
                    className="mt-2 text-[11.5px] font-bold text-coral"
                >
                    {error}
                </p>
            ) : null}
        </fieldset>
    );
}
