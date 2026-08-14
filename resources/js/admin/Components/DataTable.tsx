import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Column<T> = {
    key: string;
    header: string;
    render: (row: T) => ReactNode;
    className?: string;
};

type DataTableProps<T> = {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    emptyMessage?: string;
};

export function DataTable<T>({
    columns,
    rows,
    rowKey,
    emptyMessage = '該当するデータがありません',
}: DataTableProps<T>) {
    if (rows.length === 0) {
        return (
            <div className="flex items-center justify-center rounded-xl border border-admin-line bg-white p-12 text-sm text-admin-ink-muted">
                {emptyMessage}
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-xl border border-admin-line bg-white">
            <table className="w-full min-w-max text-left text-sm">
                <thead className="border-b border-admin-line bg-admin-sidebar-bg text-admin-ink-muted">
                    <tr>
                        {columns.map((column) => (
                            <th
                                key={column.key}
                                scope="col"
                                className={cn(
                                    'px-4 py-2 font-bold',
                                    column.className,
                                )}
                            >
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr
                            key={rowKey(row)}
                            className="border-b border-admin-line last:border-0"
                        >
                            {columns.map((column) => (
                                <td
                                    key={column.key}
                                    className={cn(
                                        'px-4 py-2',
                                        column.className,
                                    )}
                                >
                                    {column.render(row)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
