import { Badge } from '@/shared/Components/Badge';
import { TONE } from '@/shared/lib/tone';

export type StockRowData = {
    stock_id: number;
    product_name: string;
    category_name: string;
    variant_label: string;
    sku_code: string;
    has_sku: boolean;
    quantity: number;
};

type StockRowProps = {
    row: StockRowData;
    value: string;
    onChange: (value: string) => void;
    onUpdate: () => void;
};

export function StockRow({ row, value, onChange, onUpdate }: StockRowProps) {
    return (
        <tr className="border-b border-admin-line last:border-b-0">
            <td className="px-4 py-2 font-bold">{row.product_name}</td>
            <td className="px-4 py-2 text-admin-ink-muted">
                {row.variant_label}
            </td>
            <td className="px-4 py-2 font-mono text-[11.5px] text-admin-ink-muted">
                {row.sku_code}
            </td>
            <td className="px-4 py-2 text-admin-ink-muted">
                {row.category_name}
            </td>
            <td className="px-4 py-2 text-right">
                <Badge
                    tone={row.quantity > 0 ? TONE.positive : TONE.negative}
                    className="font-mono"
                >
                    {row.quantity > 0 ? `${row.quantity}` : '在庫切れ'}
                </Badge>
            </td>
            <td className="px-4 py-2">
                <label htmlFor={`quantity-${row.stock_id}`} className="sr-only">
                    {`${row.product_name}（${row.variant_label}）の在庫数`}
                </label>
                <input
                    id={`quantity-${row.stock_id}`}
                    type="number"
                    min={0}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="w-24 rounded-lg border border-admin-line px-2 py-1 text-right font-mono text-base"
                />
            </td>
            <td className="px-4 py-2 text-right">
                <button
                    type="button"
                    onClick={onUpdate}
                    className="rounded-lg border border-admin-line px-3 py-1.5 text-[11.5px] font-bold whitespace-nowrap text-admin-ink"
                >
                    更新
                </button>
            </td>
        </tr>
    );
}
