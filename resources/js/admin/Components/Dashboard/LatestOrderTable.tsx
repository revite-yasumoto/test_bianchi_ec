import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/admin/Components/Order/StatusBadge';
import type { Tone } from '@/shared/lib/tone';
import { yen } from '@/shared/lib/yen';

export type LatestOrderRow = {
    id: number;
    order_number: string;
    customer_name: string;
    total: number;
    status: string;
    status_label: string;
    status_tone: Tone;
};

type LatestOrderTableProps = {
    orders: LatestOrderRow[];
};

export function LatestOrderTable({ orders }: LatestOrderTableProps) {
    if (orders.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-admin-ink-muted">
                注文がありません
            </p>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-max text-left text-sm">
                <thead className="sr-only">
                    <tr>
                        <th scope="col">注文番号</th>
                        <th scope="col">氏名</th>
                        <th scope="col">ステータス</th>
                        <th scope="col">合計金額</th>
                        <th scope="col">操作</th>
                    </tr>
                </thead>
                <tbody>
                    {orders.map((order) => (
                        <tr
                            key={order.id}
                            className="border-b border-admin-line last:border-0"
                        >
                            <td className="py-2 pr-3 font-mono text-[11.5px]">
                                {order.order_number}
                            </td>
                            <td className="py-2 pr-3">{order.customer_name}</td>
                            <td className="py-2 pr-3">
                                <StatusBadge
                                    label={order.status_label}
                                    tone={order.status_tone}
                                />
                            </td>
                            <td className="py-2 pr-3 text-right font-mono">
                                {yen(order.total)}
                            </td>
                            <td className="py-2 text-right">
                                <Link
                                    href={route('admin.orders.show', [
                                        order.id,
                                    ])}
                                    className="text-[11.5px] font-bold text-admin-brand"
                                >
                                    詳細
                                </Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
