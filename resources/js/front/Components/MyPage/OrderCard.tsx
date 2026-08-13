import { Link } from '@inertiajs/react';
import { CancelOrderButton } from '@/front/Components/MyPage/CancelOrderButton';
import { dotDateLabel } from '@/front/lib/date';
import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';
import { yen } from '@/shared/lib/yen';

export type OrderSummary = {
    id: number;
    order_number: string;
    /** ISO 8601 の日時 */
    ordered_at: string;
    status_label: string;
    status_tone: Tone;
    total: number;
    /** 先頭の明細名に残りの点数を添えたもの（例: チームジャージ 2026 ほか1点） */
    items_summary: string;
    is_cancelable: boolean;
};

export function OrderCard({ order }: { order: OrderSummary }) {
    return (
        <article className="rounded-2xl border border-line p-5">
            <div className="flex flex-wrap items-center gap-2.5">
                <Badge tone={order.status_tone}>{order.status_label}</Badge>
                <span className="font-mono text-[13px] font-bold">
                    {order.order_number}
                </span>
                <time
                    dateTime={order.ordered_at}
                    className="text-[12px] text-ink2"
                >
                    {dotDateLabel(order.ordered_at)}
                </time>
            </div>

            <p className="mt-3 text-[13.5px] leading-[1.6] font-bold">
                {order.items_summary}
            </p>
            <p className="mt-1.5 font-mono text-[15px] font-bold">
                {yen(order.total)}
            </p>

            <div className="mt-4 flex flex-wrap gap-2.5">
                <Link
                    href={route('mypage.orders.show', [order.id])}
                    className="rounded-full bg-brand px-5 py-2 text-xs font-bold text-white"
                >
                    注文詳細
                </Link>
                {order.is_cancelable ? (
                    <CancelOrderButton
                        orderId={order.id}
                        orderNumber={order.order_number}
                    />
                ) : null}
            </div>
        </article>
    );
}
