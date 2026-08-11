import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { DataTable } from '@/admin/Components/DataTable';
import { FilterBar } from '@/admin/Components/FilterBar';
import { Pagination } from '@/admin/Components/Pagination';
import { Badge } from '@/shared/Components/Badge';
import { TONE } from '@/shared/lib/tone';
import { UserStatus } from '@/shared/lib/enums';

type MemberRow = {
    id: number;
    member_code: string;
    name: string;
    email: string;
    registered_on: string;
    status: string;
    status_label: string;
};

type Props = {
    members: Paginated<MemberRow>;
    filters: { q: string | null };
    totalCount: number;
};

export default function Index({ members, filters, totalCount }: Props) {
    const [keyword, setKeyword] = useState(filters.q ?? '');
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(
                route('admin.members.index'),
                keyword === '' ? {} : { q: keyword },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timer);
    }, [keyword]);

    return (
        <AdminLayout title="会員マスタ">
            <FilterBar
                resultCount={members.total}
                totalCount={totalCount}
                onClear={() => setKeyword('')}
            >
                <div className="min-w-[220px] flex-1">
                    <label
                        htmlFor="filter-q"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        氏名・メール・会員ID
                    </label>
                    <input
                        id="filter-q"
                        type="search"
                        value={keyword}
                        placeholder="山田 / taro@example.com / M-100238"
                        className="w-full rounded-lg border border-admin-line px-3 py-2 text-base"
                        onChange={(event) => setKeyword(event.target.value)}
                    />
                </div>
            </FilterBar>

            <div className="mt-3.5">
                <DataTable
                    columns={[
                        {
                            key: 'member_code',
                            header: '会員ID',
                            render: (row) => (
                                <span className="font-mono text-[11.5px]">
                                    {row.member_code}
                                </span>
                            ),
                        },
                        {
                            key: 'name',
                            header: '氏名',
                            render: (row) => (
                                <span className="font-bold">{row.name}</span>
                            ),
                        },
                        {
                            key: 'email',
                            header: 'メールアドレス',
                            render: (row) => (
                                <span className="text-admin-ink-muted">
                                    {row.email}
                                </span>
                            ),
                        },
                        {
                            key: 'registered_on',
                            header: '登録日',
                            render: (row) => (
                                <span className="text-admin-ink-muted">
                                    {row.registered_on}
                                </span>
                            ),
                        },
                        {
                            key: 'status',
                            header: 'ステータス',
                            render: (row) => (
                                <Badge
                                    tone={
                                        row.status === UserStatus.Active
                                            ? TONE.positive
                                            : TONE.negative
                                    }
                                >
                                    {row.status_label}
                                </Badge>
                            ),
                        },
                        {
                            key: 'actions',
                            header: '',
                            className: 'text-right',
                            render: (row) => (
                                <Link
                                    href={route('admin.members.show', [row.id])}
                                    className="block text-right text-[11.5px] font-bold text-admin-brand"
                                >
                                    詳細
                                </Link>
                            ),
                        },
                    ]}
                    rows={members.data}
                    rowKey={(row) => row.id}
                    emptyMessage="該当する会員がいません"
                />

                <Pagination links={members.links} />
            </div>
        </AdminLayout>
    );
}
