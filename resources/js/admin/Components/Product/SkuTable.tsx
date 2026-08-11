import { cn } from '@/lib/utils';
import type { SkuInput, SkuRow } from '@/admin/hooks/useSkuMatrix';

type SkuTableProps = {
    rows: SkuRow[];
    errors: Record<string, string>;
    onChange: (row: SkuRow, changes: Partial<SkuInput>) => void;
};

export function SkuTable({ rows, errors, onChange }: SkuTableProps) {
    return (
        <div className="overflow-hidden rounded-xl border border-admin-line bg-white">
            <div className="flex items-baseline gap-2.5 border-b border-admin-line px-5 py-4">
                <h2 className="text-[13px] font-extrabold text-admin-ink">
                    SKU一覧（自動生成）
                </h2>
                <p className="text-[11.5px] text-admin-ink-muted">
                    {rows.length}件
                </p>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full min-w-max text-left text-sm">
                    <thead className="border-b border-admin-line bg-admin-sidebar-bg text-admin-ink-muted">
                        <tr>
                            <th scope="col" className="px-4 py-2 font-bold">
                                カラー
                            </th>
                            <th scope="col" className="px-4 py-2 font-bold">
                                サイズ
                            </th>
                            <th scope="col" className="px-4 py-2 font-bold">
                                枝番
                            </th>
                            <th scope="col" className="px-4 py-2 font-bold">
                                SKUコード
                            </th>
                            <th
                                scope="col"
                                className="px-4 py-2 text-right font-bold"
                            >
                                在庫
                            </th>
                            <th scope="col" className="px-4 py-2 font-bold">
                                取扱
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr
                                key={row.key}
                                className="border-b border-admin-line last:border-b-0"
                            >
                                <td
                                    className={cn(
                                        'px-4 py-2',
                                        !row.is_available && 'opacity-45',
                                    )}
                                >
                                    {row.color_name}
                                </td>
                                <td
                                    className={cn(
                                        'px-4 py-2',
                                        !row.is_available && 'opacity-45',
                                    )}
                                >
                                    {row.size_name}
                                </td>
                                <td className="px-4 py-2">
                                    <label
                                        htmlFor={`branch-${row.key}`}
                                        className="sr-only"
                                    >
                                        {`${row.color_name} ${row.size_name} の枝番`}
                                    </label>
                                    <input
                                        id={`branch-${row.key}`}
                                        type="text"
                                        value={row.branch_code}
                                        disabled={!row.is_available}
                                        onChange={(event) =>
                                            onChange(row, {
                                                branch_code: event.target.value,
                                            })
                                        }
                                        className="w-20 rounded-lg border border-admin-line px-2 py-1 font-mono text-base disabled:bg-admin-sidebar-bg"
                                    />
                                    {errors[`variants.${index}.branch_code`] ? (
                                        <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                            {
                                                errors[
                                                    `variants.${index}.branch_code`
                                                ]
                                            }
                                        </p>
                                    ) : null}
                                </td>
                                <td
                                    className={cn(
                                        'px-4 py-2 font-mono text-[11.5px] text-admin-ink-muted',
                                        !row.is_available && 'opacity-45',
                                    )}
                                >
                                    {row.sku_code}
                                </td>
                                <td className="px-4 py-2 text-right">
                                    <label
                                        htmlFor={`quantity-${row.key}`}
                                        className="sr-only"
                                    >
                                        {`${row.color_name} ${row.size_name} の在庫数`}
                                    </label>
                                    <input
                                        id={`quantity-${row.key}`}
                                        type="number"
                                        min={0}
                                        value={row.quantity}
                                        disabled={!row.is_available}
                                        onChange={(event) =>
                                            onChange(row, {
                                                quantity: Number(
                                                    event.target.value,
                                                ),
                                            })
                                        }
                                        className="w-24 rounded-lg border border-admin-line px-2 py-1 text-right font-mono text-base disabled:bg-admin-sidebar-bg"
                                    />
                                </td>
                                <td className="px-4 py-2">
                                    <button
                                        type="button"
                                        aria-pressed={row.is_available}
                                        className={cn(
                                            'rounded-full border px-2.5 py-1 text-[11px] font-bold whitespace-nowrap',
                                            row.is_available
                                                ? 'border-admin-brand text-admin-brand'
                                                : 'border-admin-line text-admin-ink-muted',
                                        )}
                                        onClick={() =>
                                            onChange(row, {
                                                is_available: !row.is_available,
                                            })
                                        }
                                    >
                                        {row.is_available
                                            ? '取扱あり'
                                            : '規格なし'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
