import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { FilterBar } from '@/admin/Components/FilterBar';
import { Pagination } from '@/admin/Components/Pagination';
import { StockRow, type StockRowData } from './StockRow';

export type StockFilters = {
    has_sku: string;
    category_id: number | null;
    q: string | null;
};

export type StockManagerProps = {
    stocks: Paginated<StockRowData>;
    categories: { id: number; name: string }[];
    filters: StockFilters;
    totalCount: number;
};

type FilterFields = {
    has_sku: string;
    category_id: string;
    q: string;
};

const EMPTY_FILTERS: FilterFields = {
    has_sku: 'all',
    category_id: '',
    q: '',
};

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

function toQuantities(rows: StockRowData[]): Record<number, string> {
    return Object.fromEntries(
        rows.map((row) => [row.stock_id, String(row.quantity)]),
    );
}

export function StockManager({
    stocks,
    categories,
    filters,
    totalCount,
}: StockManagerProps) {
    const { showToast } = useAdminToast();
    const [fields, setFields] = useState<FilterFields>({
        has_sku: filters.has_sku,
        category_id: filters.category_id ? String(filters.category_id) : '',
        q: filters.q ?? '',
    });
    const [quantities, setQuantities] = useState(() =>
        toQuantities(stocks.data),
    );
    const [pendingRow, setPendingRow] = useState<StockRowData | null>(null);
    const [isBulkOpen, setIsBulkOpen] = useState(false);
    const isFirstRender = useRef(true);

    // 絞り込みやページ送りで表示行が入れ替わったら、入力欄をサーバーの値に戻す
    const rowsKey = stocks.data.map((row) => row.stock_id).join(',');
    const [seenRowsKey, setSeenRowsKey] = useState(rowsKey);

    if (seenRowsKey !== rowsKey) {
        setSeenRowsKey(rowsKey);
        setQuantities(toQuantities(stocks.data));
    }

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(route('admin.stocks.index'), toQuery(fields), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 400);

        return () => clearTimeout(timer);
    }, [fields]);

    const updateField = (field: keyof FilterFields, value: string) => {
        setFields((previous) => ({ ...previous, [field]: value }));
    };

    const submitSingle = (row: StockRowData) => {
        setPendingRow(null);

        router.put(
            route('admin.stocks.update', [row.stock_id]),
            { quantity: Number(quantities[row.stock_id] ?? 0) },
            {
                preserveScroll: true,
                onSuccess: () => showToast('在庫を更新しました'),
            },
        );
    };

    const submitBulk = () => {
        setIsBulkOpen(false);

        router.put(
            route('admin.stocks.bulk-update'),
            {
                stocks: stocks.data.map((row) => ({
                    id: row.stock_id,
                    quantity: Number(quantities[row.stock_id] ?? 0),
                })),
                ...toQuery(fields),
                page: stocks.current_page,
            },
            {
                preserveScroll: true,
                onSuccess: () =>
                    showToast(`${stocks.data.length}件の在庫を更新しました`),
                onError: (errors) =>
                    showToast(errors.stocks ?? '一括更新に失敗しました'),
            },
        );
    };

    return (
        <>
            <FilterBar
                resultCount={stocks.total}
                totalCount={totalCount}
                onClear={() => setFields(EMPTY_FILTERS)}
            >
                <div className="w-[140px]">
                    <label htmlFor="filter-has-sku" className={LABEL_CLASS}>
                        SKU区分
                    </label>
                    <select
                        id="filter-has-sku"
                        value={fields.has_sku}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            updateField('has_sku', event.target.value)
                        }
                    >
                        <option value="all">すべて</option>
                        <option value="with">SKUあり</option>
                        <option value="without">SKUなし</option>
                    </select>
                </div>

                <div className="w-[150px]">
                    <label htmlFor="filter-category" className={LABEL_CLASS}>
                        カテゴリ
                    </label>
                    <select
                        id="filter-category"
                        value={fields.category_id}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            updateField('category_id', event.target.value)
                        }
                    >
                        <option value="">すべて</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="min-w-[180px] flex-1">
                    <label htmlFor="filter-q" className={LABEL_CLASS}>
                        商品名
                    </label>
                    <input
                        id="filter-q"
                        type="search"
                        value={fields.q}
                        placeholder="商品名で検索"
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            updateField('q', event.target.value)
                        }
                    />
                </div>

                <button
                    type="button"
                    disabled={stocks.data.length === 0}
                    className="self-end rounded-lg bg-admin-brand-deep px-4 py-2 text-[12.5px] font-bold whitespace-nowrap text-white disabled:opacity-50"
                    onClick={() => setIsBulkOpen(true)}
                >
                    表示中を一括更新
                </button>
            </FilterBar>

            <div className="mt-3.5">
                {stocks.data.length === 0 ? (
                    <div className="flex items-center justify-center rounded-xl border border-admin-line bg-white p-12 text-sm text-admin-ink-muted">
                        該当する在庫データがありません
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-admin-line bg-white">
                        <table className="w-full min-w-max text-left text-sm">
                            <thead className="border-b border-admin-line bg-admin-sidebar-bg text-admin-ink-muted">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-bold"
                                    >
                                        商品名
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-bold"
                                    >
                                        バリエーション
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-bold"
                                    >
                                        SKUコード
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-bold"
                                    >
                                        カテゴリ
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 text-right font-bold"
                                    >
                                        現在庫
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-bold"
                                    >
                                        在庫数変更
                                    </th>
                                    <th scope="col" className="px-4 py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {stocks.data.map((row) => (
                                    <StockRow
                                        key={row.stock_id}
                                        row={row}
                                        value={quantities[row.stock_id] ?? ''}
                                        onChange={(value) =>
                                            setQuantities((previous) => ({
                                                ...previous,
                                                [row.stock_id]: value,
                                            }))
                                        }
                                        onUpdate={() => setPendingRow(row)}
                                    />
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={stocks.links} />
            </div>

            <ConfirmDialog
                isOpen={pendingRow !== null}
                title="在庫数の更新"
                message={
                    pendingRow
                        ? `${pendingRow.product_name}（${pendingRow.variant_label}）の在庫数を更新します。この操作は取り消せません。`
                        : ''
                }
                confirmLabel="更新する"
                onConfirm={() => pendingRow && submitSingle(pendingRow)}
                onCancel={() => setPendingRow(null)}
            />

            <ConfirmDialog
                isOpen={isBulkOpen}
                title="在庫数の一括更新"
                message={`現在表示されている ${stocks.data.length} 件の在庫数を入力値で更新します。この操作は取り消せません。`}
                confirmLabel="一括更新する"
                onConfirm={submitBulk}
                onCancel={() => setIsBulkOpen(false)}
            />
        </>
    );
}

function toQuery(fields: FilterFields): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(fields)) {
        if (value !== '' && !(key === 'has_sku' && value === 'all')) {
            query[key] = value;
        }
    }

    return query;
}
