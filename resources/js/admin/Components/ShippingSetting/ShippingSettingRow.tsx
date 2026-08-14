import { cn } from '@/lib/utils';

export type ShippingSettingRowData = {
    id: number;
    prefecture_id: number;
    prefecture_name: string;
    fee: number;
    delivery_days: number;
};

type ShippingSettingRowProps = {
    row: ShippingSettingRowData;
    fee: string;
    deliveryDays: string;
    feeError?: string;
    deliveryDaysError?: string;
    onChangeFee: (value: string) => void;
    onChangeDeliveryDays: (value: string) => void;
};

/**
 * 数値のスピナーは入力欄が狭いと右寄せした数値に重なって読めなくなるため隠す。
 * 増減はキーボードの上下キーで従来どおり行える。
 *
 * スピナーの消し方はブラウザで異なり標準の手段が無いため、`appearance: textfield`（Firefox）と
 * WebKit 専用の擬似要素（Chrome・Safari）を併記する。ネイティブ外観を落とすとフォーカスリングも
 * 消えるため、代替のフォーカススタイルを併せて指定する。
 */
const INPUT_CLASS =
    'w-full min-w-0 appearance-none rounded-md border border-admin-line px-2 py-1 text-right font-mono text-base [appearance:textfield] focus:border-admin-brand focus:outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

export function ShippingSettingRow({
    row,
    fee,
    deliveryDays,
    feeError,
    deliveryDaysError,
    onChangeFee,
    onChangeDeliveryDays,
}: ShippingSettingRowProps) {
    const feeId = `shipping-fee-${row.id}`;
    const deliveryDaysId = `shipping-delivery-days-${row.id}`;
    const hasError = feeError !== undefined || deliveryDaysError !== undefined;

    return (
        <li
            className={cn(
                'flex items-center gap-2 rounded-lg border border-admin-line px-2.5 py-1.5',
                hasError && 'border-admin-danger',
            )}
        >
            <span className="w-16 flex-shrink-0 text-[12.5px] font-semibold text-admin-ink">
                {row.prefecture_name}
            </span>

            <label htmlFor={feeId} className="sr-only">
                {`${row.prefecture_name}の送料`}
            </label>
            <input
                id={feeId}
                type="number"
                min={0}
                max={100000}
                value={fee}
                className={INPUT_CLASS}
                aria-invalid={feeError !== undefined}
                onChange={(event) => onChangeFee(event.target.value)}
            />
            <span className="text-[11px] text-admin-ink-muted">円</span>

            <label htmlFor={deliveryDaysId} className="sr-only">
                {`${row.prefecture_name}の配送予定日数`}
            </label>
            <input
                id={deliveryDaysId}
                type="number"
                min={1}
                max={30}
                value={deliveryDays}
                className={cn(INPUT_CLASS, 'w-16')}
                aria-invalid={deliveryDaysError !== undefined}
                onChange={(event) => onChangeDeliveryDays(event.target.value)}
            />
            <span className="text-[11px] text-admin-ink-muted">日</span>
        </li>
    );
}
