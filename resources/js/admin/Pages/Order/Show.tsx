import { Link } from '@inertiajs/react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { StatusBadge } from '@/admin/Components/Order/StatusBadge';
import { StatusUpdateCard } from '@/admin/Components/Order/StatusUpdateCard';
import type { Tone } from '@/shared/lib/tone';
import { yen } from '@/shared/lib/yen';

type OrderItemRow = {
    id: number;
    product_name: string;
    category_name: string;
    variant_label: string;
    sku_code: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
};

type OrderDetail = {
    id: number;
    order_number: string;
    ordered_at: string;
    status: string;
    status_label: string;
    status_tone: Tone;
    payment_method_label: string;
    items: OrderItemRow[];
    subtotal: number;
    shipping_fee: number;
    cod_fee: number;
    total: number;
    estimated_delivery_date: string;
    bank_transfer_note: string | null;
    shipping: {
        recipient_name: string;
        postal_code: string;
        prefecture_name: string;
        city: string;
        address_line1: string;
        address_line2: string | null;
        tel: string;
    };
    customer: {
        member_code: string;
        name: string;
        email: string;
        tel: string | null;
    };
    histories: {
        id: number;
        from_status_label: string | null;
        to_status_label: string;
        admin_name: string | null;
        changed_at: string;
    }[];
};

type Props = {
    order: OrderDetail;
    statusOptions: { value: string; label: string }[];
    errors: Record<string, string>;
};

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white p-5';

const HEADING_CLASS = 'mb-2.5 text-[13px] font-extrabold text-admin-ink';

function AmountRow({
    label,
    amount,
    emphasized = false,
}: {
    label: string;
    amount: number;
    emphasized?: boolean;
}) {
    return (
        <div
            className={
                emphasized
                    ? 'flex justify-between border-t border-admin-line pt-2 text-[15px] font-extrabold'
                    : 'flex justify-between text-[12.5px]'
            }
        >
            <span>{label}</span>
            <span className="font-mono">{yen(amount)}</span>
        </div>
    );
}

export default function Show({ order, statusOptions, errors }: Props) {
    return (
        <AdminLayout title={`注文 ${order.order_number}`}>
            <Link
                href={route('admin.orders.index')}
                className="mb-3.5 inline-block font-mono text-[11px] text-admin-ink-muted"
            >
                ← 注文一覧へ
            </Link>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <StatusBadge
                    label={order.status_label}
                    tone={order.status_tone}
                />
                <span className="text-[12.5px] text-admin-ink-muted">
                    注文日時 {order.ordered_at}
                </span>
            </div>

            <div className="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start gap-4">
                <div className={CARD_CLASS}>
                    <h2 className={HEADING_CLASS}>注文商品</h2>
                    <table className="w-full text-left text-sm">
                        <tbody>
                            {order.items.map((item) => (
                                <tr
                                    key={item.id}
                                    className="border-b border-admin-line last:border-b-0"
                                >
                                    <td className="py-2.5">
                                        <span className="font-bold">
                                            {item.product_name}
                                        </span>
                                        <span className="mt-0.5 block text-[11px] text-admin-ink-muted">
                                            {item.variant_label}
                                            {item.sku_code
                                                ? ` / ${item.sku_code}`
                                                : ''}
                                        </span>
                                        <span className="block text-[11px] text-admin-ink-muted">
                                            {yen(item.unit_price)}
                                        </span>
                                    </td>
                                    <td className="py-2.5 text-right text-admin-ink-muted">
                                        ×{item.quantity}
                                    </td>
                                    <td className="py-2.5 text-right font-mono font-bold">
                                        {yen(item.subtotal)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="mt-3.5 flex flex-col gap-1.5 border-t border-admin-line pt-3">
                        <AmountRow label="商品合計" amount={order.subtotal} />
                        <AmountRow label="送料" amount={order.shipping_fee} />
                        {order.cod_fee > 0 ? (
                            <AmountRow
                                label="代引き手数料"
                                amount={order.cod_fee}
                            />
                        ) : null}
                        <AmountRow
                            label="合計"
                            amount={order.total}
                            emphasized
                        />
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>お届け先</h2>
                        <p className="text-[12.5px] leading-loose text-admin-ink-muted">
                            {order.shipping.recipient_name}
                            <br />〒{order.shipping.postal_code}
                            <br />
                            {order.shipping.prefecture_name}
                            {order.shipping.city}
                            {order.shipping.address_line1}
                            {order.shipping.address_line2 ? (
                                <>
                                    <br />
                                    {order.shipping.address_line2}
                                </>
                            ) : null}
                            <br />
                            {order.shipping.tel}
                        </p>

                        <h2 className={`${HEADING_CLASS} mt-4`}>
                            お支払い・配送
                        </h2>
                        <p className="text-[12.5px] leading-loose text-admin-ink-muted">
                            {order.payment_method_label}
                            <br />
                            お届け予定日 {order.estimated_delivery_date}
                        </p>

                        <h2 className={`${HEADING_CLASS} mt-4`}>ご注文者</h2>
                        <p className="text-[12.5px] leading-loose text-admin-ink-muted">
                            <span className="font-mono">
                                {order.customer.member_code}
                            </span>
                            <br />
                            {order.customer.name}
                            <br />
                            {order.customer.email}
                            {order.customer.tel ? (
                                <>
                                    <br />
                                    {order.customer.tel}
                                </>
                            ) : null}
                        </p>
                    </div>

                    <StatusUpdateCard
                        orderId={order.id}
                        currentStatusLabel={order.status_label}
                        statusOptions={statusOptions}
                        error={errors.status}
                    />

                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>ステータス変更履歴</h2>
                        {order.histories.length === 0 ? (
                            <p className="text-[12.5px] text-admin-ink-muted">
                                変更履歴はありません。
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-2">
                                {order.histories.map((history) => (
                                    <li
                                        key={history.id}
                                        className="border-b border-admin-line pb-2 text-[12px] last:border-b-0 last:pb-0"
                                    >
                                        <span className="font-bold">
                                            {history.from_status_label
                                                ? `${history.from_status_label} → ${history.to_status_label}`
                                                : history.to_status_label}
                                        </span>
                                        <span className="mt-0.5 block text-[11px] text-admin-ink-muted">
                                            {history.changed_at}
                                            {history.admin_name
                                                ? ` / ${history.admin_name}`
                                                : ' / 会員による操作'}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
