import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { DataTable } from '@/admin/Components/DataTable';
import { FilterBar } from '@/admin/Components/FilterBar';
import { Pagination } from '@/admin/Components/Pagination';
import { Badge } from '@/shared/Components/Badge';
import { TONE } from '@/shared/lib/tone';
import { yen } from '@/shared/lib/yen';

type ProductRow = {
    id: number;
    product_code: string;
    name: string;
    category_name: string;
    price: number;
    total_stock: number;
    has_sku: boolean;
    is_published: boolean;
};

type Filters = {
    q: string | null;
    category_id: number | null;
    has_sku: string;
    price_min: number | null;
    price_max: number | null;
};

type Props = {
    products: Paginated<ProductRow>;
    categories: { id: number; name: string }[];
    filters: Filters;
    totalCount: number;
};

type FilterFields = {
    q: string;
    category_id: string;
    has_sku: string;
    price_min: string;
    price_max: string;
};

const EMPTY_FILTERS: FilterFields = {
    q: '',
    category_id: '',
    has_sku: 'all',
    price_min: '',
    price_max: '',
};

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

export default function Index({
    products,
    categories,
    filters,
    totalCount,
}: Props) {
    const [fields, setFields] = useState<FilterFields>({
        q: filters.q ?? '',
        category_id: filters.category_id ? String(filters.category_id) : '',
        has_sku: filters.has_sku,
        price_min: filters.price_min !== null ? String(filters.price_min) : '',
        price_max: filters.price_max !== null ? String(filters.price_max) : '',
    });
    const isFirstRender = useRef(true);

    // 入力のたびに往復させないよう、最後の変更から一定時間おいてから絞り込みを反映する
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(route('admin.products.index'), toQuery(fields), {
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
        <AdminLayout
            title="商品一覧"
            headerActions={
                <Link
                    href={route('admin.products.create')}
                    className="rounded-lg bg-admin-brand px-4 py-2 text-[12.5px] font-bold text-white"
                >
                    ＋ 商品登録
                </Link>
            }
        >
            <FilterBar
                resultCount={products.total}
                totalCount={totalCount}
                onClear={() => setFields(EMPTY_FILTERS)}
            >
                <div className="min-w-[180px] flex-1">
                    <label htmlFor="filter-q" className={LABEL_CLASS}>
                        商品名・商品ID
                    </label>
                    <input
                        id="filter-q"
                        type="search"
                        value={fields.q}
                        placeholder="ROADSTER / RC7-105"
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            updateField('q', event.target.value)
                        }
                    />
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

                <div className="w-[120px]">
                    <label htmlFor="filter-has-sku" className={LABEL_CLASS}>
                        SKU有無
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

                <div className="w-[190px]">
                    <span className={LABEL_CLASS}>価格帯</span>
                    <div className="flex items-center gap-1.5">
                        <label htmlFor="filter-price-min" className="sr-only">
                            価格の下限
                        </label>
                        <input
                            id="filter-price-min"
                            type="number"
                            min={0}
                            value={fields.price_min}
                            placeholder="0"
                            className={FIELD_CLASS}
                            onChange={(event) =>
                                updateField('price_min', event.target.value)
                            }
                        />
                        <span className="text-xs text-admin-ink-muted">〜</span>
                        <label htmlFor="filter-price-max" className="sr-only">
                            価格の上限
                        </label>
                        <input
                            id="filter-price-max"
                            type="number"
                            min={0}
                            value={fields.price_max}
                            placeholder="上限なし"
                            className={FIELD_CLASS}
                            onChange={(event) =>
                                updateField('price_max', event.target.value)
                            }
                        />
                    </div>
                </div>
            </FilterBar>

            <div className="mt-3.5">
                <DataTable
                    columns={[
                        {
                            key: 'product_code',
                            header: '商品ID',
                            render: (row) => (
                                <span className="font-mono text-[11.5px]">
                                    {row.product_code}
                                </span>
                            ),
                        },
                        {
                            key: 'name',
                            header: '商品名',
                            render: (row) => (
                                <span className="font-bold">{row.name}</span>
                            ),
                        },
                        {
                            key: 'category_name',
                            header: 'カテゴリ',
                            render: (row) => (
                                <span className="text-admin-ink-muted">
                                    {row.category_name}
                                </span>
                            ),
                        },
                        {
                            key: 'price',
                            header: '価格',
                            className: 'text-right',
                            render: (row) => (
                                <span className="block text-right font-mono">
                                    {yen(row.price)}
                                </span>
                            ),
                        },
                        {
                            key: 'total_stock',
                            header: '在庫',
                            render: (row) => (
                                <Badge
                                    tone={
                                        row.total_stock > 0
                                            ? TONE.positive
                                            : TONE.negative
                                    }
                                >
                                    {row.total_stock > 0
                                        ? `在庫 ${row.total_stock}`
                                        : '在庫切れ'}
                                </Badge>
                            ),
                        },
                        {
                            key: 'has_sku',
                            header: 'SKU',
                            render: (row) => (
                                <span className="text-admin-ink-muted">
                                    {row.has_sku ? 'あり' : 'なし'}
                                </span>
                            ),
                        },
                        {
                            key: 'is_published',
                            header: '公開',
                            render: (row) => (
                                <Badge
                                    tone={
                                        row.is_published
                                            ? TONE.info
                                            : TONE.warning
                                    }
                                >
                                    {row.is_published ? '公開' : '非公開'}
                                </Badge>
                            ),
                        },
                        {
                            key: 'actions',
                            header: '',
                            className: 'text-right',
                            render: (row) => (
                                <Link
                                    href={route('admin.products.edit', [
                                        row.id,
                                    ])}
                                    className="block text-right text-[11.5px] font-bold text-admin-brand"
                                >
                                    編集
                                </Link>
                            ),
                        },
                    ]}
                    rows={products.data}
                    rowKey={(row) => row.id}
                    emptyMessage="該当する商品がありません"
                />

                <Pagination links={products.links} />
            </div>
        </AdminLayout>
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
