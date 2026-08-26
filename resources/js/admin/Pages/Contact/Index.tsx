import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { StatusBadge } from '@/admin/Components/Contact/StatusBadge';
import { TabNav } from '@/admin/Components/Contact/TabNav';
import { DataTable, type Column } from '@/admin/Components/DataTable';
import { FilterBar } from '@/admin/Components/FilterBar';
import { Pagination } from '@/admin/Components/Pagination';
import { cn } from '@/lib/utils';
import { ContactTab } from '@/shared/lib/enums';
import type { Tone } from '@/shared/lib/tone';

type ContactRow = {
    id: number;
    received_at: string;
    name: string;
    email: string;
    body_excerpt: string;
    product_name: string | null;
    product_code: string | null;
    status: string;
    status_label: string;
    status_tone: Tone;
};

type Props = {
    contacts: Paginated<ContactRow>;
    filters: {
        tab: ContactTab;
        status: string;
        q: string | null;
        from: string | null;
        to: string | null;
    };
    totalCount: number;
    statusOptions: { value: string; label: string }[];
};

type FilterFields = { status: string; q: string; from: string; to: string };

const EMPTY_FILTERS: FilterFields = { status: 'all', q: '', from: '', to: '' };

const KEYWORD_DEBOUNCE_MS = 400;

// 管理画面の絞り込みは枠線だけの見た目で操作できるか分かりにくいため、ホバーで枠線を強調する
const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base transition-colors hover:border-admin-brand';

// カレンダーアイコンは input の shadow DOM 内にあり枠線のホバーでは変化しないため、アイコン自体にも指定する（Chromium 系のみ有効）
const DATE_FIELD_CLASS = `${FIELD_CLASS} [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:rounded [&::-webkit-calendar-picker-indicator:hover]:bg-admin-line`;

const LABEL_CLASS = 'mb-1.5 block text-sm font-bold text-admin-ink';

const DATE_RANGE_ERROR_ID = 'filter-received-at-error';

export default function Index({
    contacts,
    filters,
    totalCount,
    statusOptions,
}: Props) {
    const [fields, setFields] = useState<FilterFields>({
        status: filters.status,
        q: filters.q ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });
    const isFirstRender = useRef(true);
    // キーワードだけ入力の途中経過で送らないよう待ち、選択式の項目は即時に反映する
    const delayRef = useRef(0);
    const isDateRangeInvalid =
        fields.from !== '' && fields.to !== '' && fields.from > fields.to;

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        if (isDateRangeInvalid) {
            return;
        }

        const timer = setTimeout(() => {
            router.get(
                route('admin.contacts.index'),
                { tab: filters.tab, ...toQuery(fields) },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, delayRef.current);

        return () => clearTimeout(timer);
    }, [fields, filters.tab, isDateRangeInvalid]);

    const updateField = (field: keyof FilterFields, value: string) => {
        delayRef.current = field === 'q' ? KEYWORD_DEBOUNCE_MS : 0;
        setFields((previous) => ({ ...previous, [field]: value }));
    };

    const clearFilters = () => {
        delayRef.current = 0;
        setFields(EMPTY_FILTERS);
    };

    // 期間が不正な間は検索を送っていないため、一覧に出ている条件（適用済みの絞り込み）を引き継ぐ
    const query = toQuery(
        isDateRangeInvalid
            ? {
                  status: filters.status,
                  q: filters.q ?? '',
                  from: filters.from ?? '',
                  to: filters.to ?? '',
              }
            : fields,
    );

    return (
        <AdminLayout
            title="お問い合わせ管理"
            headerActions={
                <a
                    href={route('admin.contacts.csv.export', {
                        tab: filters.tab,
                        ...query,
                    })}
                    className="rounded-lg border border-admin-line bg-white px-3.5 py-2 text-[15px] font-bold whitespace-nowrap text-admin-ink"
                >
                    絞り込み結果をCSVエクスポート
                </a>
            }
        >
            <TabNav current={filters.tab} query={query} />

            <div className="mt-3.5">
                <FilterBar
                    resultCount={contacts.total}
                    totalCount={totalCount}
                    onClear={clearFilters}
                >
                    <div className="w-[150px]">
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

                    <fieldset className="w-[260px]">
                        <legend className={LABEL_CLASS}>受信日</legend>
                        <div className="flex items-center gap-1.5">
                            <input
                                type="date"
                                aria-label="受信日（開始）"
                                value={fields.from}
                                aria-invalid={isDateRangeInvalid}
                                aria-describedby={
                                    isDateRangeInvalid
                                        ? DATE_RANGE_ERROR_ID
                                        : undefined
                                }
                                className={cn(
                                    DATE_FIELD_CLASS,
                                    isDateRangeInvalid && 'border-admin-danger',
                                )}
                                onChange={(event) =>
                                    updateField('from', event.target.value)
                                }
                            />
                            <span className="text-sm text-admin-ink-muted">
                                〜
                            </span>
                            <input
                                type="date"
                                aria-label="受信日（終了）"
                                value={fields.to}
                                aria-invalid={isDateRangeInvalid}
                                aria-describedby={
                                    isDateRangeInvalid
                                        ? DATE_RANGE_ERROR_ID
                                        : undefined
                                }
                                className={cn(
                                    DATE_FIELD_CLASS,
                                    isDateRangeInvalid && 'border-admin-danger',
                                )}
                                onChange={(event) =>
                                    updateField('to', event.target.value)
                                }
                            />
                        </div>
                        {isDateRangeInvalid ? (
                            <p
                                id={DATE_RANGE_ERROR_ID}
                                role="alert"
                                className="mt-1.5 text-sm font-bold text-admin-danger"
                            >
                                終了日は開始日以降の日付を指定してください
                            </p>
                        ) : null}
                    </fieldset>

                    <div className="min-w-[200px] flex-1">
                        <label htmlFor="filter-q" className={LABEL_CLASS}>
                            キーワード（お名前・メール・対象商品・本文）
                        </label>
                        <input
                            id="filter-q"
                            type="search"
                            value={fields.q}
                            placeholder="山田 / RC7 / 納期"
                            className={FIELD_CLASS}
                            onChange={(event) =>
                                updateField('q', event.target.value)
                            }
                        />
                    </div>
                </FilterBar>
            </div>

            <div className="mt-3.5">
                <DataTable
                    columns={columnsOf(filters.tab)}
                    rows={contacts.data}
                    rowKey={(row) => row.id}
                    emptyMessage="該当するお問い合わせがありません"
                />

                <div className="flex flex-wrap items-center justify-center gap-3">
                    <Pagination links={contacts.links} />
                    <span className="mt-4 text-sm text-admin-ink-muted">
                        1ページ50件
                    </span>
                </div>
            </div>
        </AdminLayout>
    );
}

function columnsOf(tab: ContactTab): Column<ContactRow>[] {
    const columns: Column<ContactRow>[] = [
        {
            key: 'received_at',
            header: '受信日時',
            render: (row: ContactRow) => (
                <span className="whitespace-nowrap text-admin-ink-muted">
                    {row.received_at}
                </span>
            ),
        },
        {
            key: 'name',
            header: 'お名前',
            render: (row: ContactRow) => row.name,
        },
        {
            key: 'email',
            header: 'メールアドレス',
            render: (row: ContactRow) => (
                <span className="text-admin-ink-muted">{row.email}</span>
            ),
        },
    ];

    if (tab === ContactTab.Product) {
        columns.push({
            key: 'product_name',
            header: '対象商品',
            render: (row: ContactRow) => (
                <div className="whitespace-nowrap">
                    <p className="text-sm font-semibold">
                        {row.product_name ?? '—'}
                    </p>
                    {row.product_code ? (
                        <p className="font-mono text-sm text-admin-ink-muted">
                            {row.product_code}
                        </p>
                    ) : null}
                </div>
            ),
        });
    }

    return [
        ...columns,
        {
            key: 'body_excerpt',
            header: '本文（抜粋）',
            className: 'max-w-[320px]',
            render: (row: ContactRow) => (
                <span className="block truncate">{row.body_excerpt}</span>
            ),
        },
        {
            key: 'status',
            header: 'ステータス',
            render: (row: ContactRow) => (
                <StatusBadge label={row.status_label} tone={row.status_tone} />
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row: ContactRow) => (
                <Link
                    href={route('admin.contacts.show', [row.id])}
                    className="block text-right text-sm font-bold text-admin-brand"
                >
                    詳細
                </Link>
            ),
        },
    ];
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
