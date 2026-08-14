import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { DataTable } from '@/admin/Components/DataTable';
import { FilterBar } from '@/admin/Components/FilterBar';
import { Pagination } from '@/admin/Components/Pagination';
import { StatusBadge } from '@/admin/Components/Order/StatusBadge';
import type { Tone } from '@/shared/lib/tone';
import { yen } from '@/shared/lib/yen';

type OrderRow = {
    id: number;
    order_number: string;
    ordered_at: string;
    customer_name: string;
    total: number;
    payment_method_label: string;
    status: string;
    status_label: string;
    status_tone: Tone;
};

type Props = {
    orders: Paginated<OrderRow>;
    filters: { status: string; q: string | null };
    totalCount: number;
    statusOptions: { value: string; label: string }[];
};

type FilterFields = { status: string; q: string };

const EMPTY_FILTERS: FilterFields = { status: 'all', q: '' };

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

export default function Index({
    orders,
    filters,
    totalCount,
    statusOptions,
}: Props) {
    const [fields, setFields] = useState<FilterFields>({
        status: filters.status,
        q: filters.q ?? '',
    });
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(route('admin.orders.index'), toQuery(fields), {
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

    return (
        <AdminLayout title="注文管理">
            <FilterBar
                resultCount={orders.total}
                totalCount={totalCount}
                onClear={() => setFields(EMPTY_FILTERS)}
            >
                <div className="w-[170px]">
                    <label htmlFor="filter-status" className={LABEL_CLASS}>
                        ステータス
                    </label>
                    <select
                        id="filter-status"
                        value={fields.status}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            updateField('status', event.target.value)
                        }
                    >
                        <option value="all">すべて</option>
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="min-w-[200px] flex-1">
                    <label htmlFor="filter-q" className={LABEL_CLASS}>
                        注文番号・顧客名
                    </label>
                    <input
                        id="filter-q"
                        type="search"
                        value={fields.q}
                        placeholder="BNC-2607-0918 / 山田"
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            updateField('q', event.target.value)
                        }
                    />
                </div>
            </FilterBar>

            <div className="mt-3.5">
                <DataTable
                    columns={[
                        {
                            key: 'order_number',
                            header: '注文番号',
                            render: (row) => (
                                <span className="font-mono text-[11.5px] font-bold">
                                    {row.order_number}
                                </span>
                            ),
                        },
                        {
                            key: 'ordered_at',
                            header: '注文日',
                            render: (row) => (
                                <span className="text-admin-ink-muted">
                                    {row.ordered_at}
                                </span>
                            ),
                        },
                        {
                            key: 'customer_name',
                            header: '顧客名',
                            render: (row) => row.customer_name,
                        },
                        {
                            key: 'total',
                            header: '金額',
                            className: 'text-right',
                            render: (row) => (
                                <span className="block text-right font-mono font-bold">
                                    {yen(row.total)}
                                </span>
                            ),
                        },
                        {
                            key: 'payment_method_label',
                            header: '支払方法',
                            render: (row) => (
                                <span className="text-admin-ink-muted">
                                    {row.payment_method_label}
                                </span>
                            ),
                        },
                        {
                            key: 'status',
                            header: 'ステータス',
                            render: (row) => (
                                <StatusBadge
                                    label={row.status_label}
                                    tone={row.status_tone}
                                />
                            ),
                        },
                        {
                            key: 'actions',
                            header: '',
                            className: 'text-right',
                            render: (row) => (
                                <Link
                                    href={route('admin.orders.show', [row.id])}
                                    className="block text-right text-[11.5px] font-bold text-admin-brand"
                                >
                                    詳細
                                </Link>
                            ),
                        },
                    ]}
                    rows={orders.data}
                    rowKey={(row) => row.id}
                    emptyMessage="該当する注文がありません"
                />

                <Pagination links={orders.links} />
            </div>
        </AdminLayout>
    );
}

function toQuery(fields: FilterFields): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(fields)) {
        if (value !== '' && !(key === 'status' && value === 'all')) {
            query[key] = value;
        }
    }

    return query;
}
