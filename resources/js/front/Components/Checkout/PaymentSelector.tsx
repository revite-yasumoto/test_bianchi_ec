import { cn } from '@/lib/utils';
import { PaymentMethod } from '@/shared/lib/enums';
import { yen } from '@/shared/lib/yen';

type PaymentSelectorProps = {
    selected: PaymentMethod;
    codFee: number;
    onChange: (paymentMethod: PaymentMethod) => void;
};

export function PaymentSelector({
    selected,
    codFee,
    onChange,
}: PaymentSelectorProps) {
    const options = [
        {
            value: PaymentMethod.BankTransfer,
            label: '銀行振込（前払い）',
            note: 'ご入金確認後に発送準備を開始します。振込手数料はお客様負担となります。',
        },
        {
            value: PaymentMethod.Cod,
            label: '代金引換',
            note: `商品到着時にお支払いいただきます。代引き手数料 ${yen(codFee)} が加算されます。`,
        },
    ];

    return (
        <fieldset>
            <legend className="mb-3 text-[15px] font-extrabold">
                お支払い方法
            </legend>

            <div className="flex flex-col gap-2.5">
                {options.map((option) => {
                    const isSelected = option.value === selected;

                    return (
                        <label
                            key={option.value}
                            className={cn(
                                'flex cursor-pointer gap-3 rounded-2xl border-[1.5px] px-4.5 py-4',
                                isSelected
                                    ? 'border-brand bg-[#F3F8FA]'
                                    : 'border-line bg-white',
                            )}
                        >
                            <input
                                type="radio"
                                name="payment_method"
                                value={option.value}
                                checked={isSelected}
                                onChange={() => onChange(option.value)}
                                className="mt-0.5 h-4.5 w-4.5 shrink-0 accent-brand"
                            />
                            <span>
                                <span className="block text-[13.5px] font-bold">
                                    {option.label}
                                </span>
                                <span className="mt-1 block text-[12.5px] leading-[1.6] text-ink2">
                                    {option.note}
                                </span>
                            </span>
                        </label>
                    );
                })}
            </div>

            <p className="mt-2.5 text-[11.5px] text-ink2">
                クレジットカード等のオンライン決済はお取り扱いしておりません。
            </p>
        </fieldset>
    );
}
