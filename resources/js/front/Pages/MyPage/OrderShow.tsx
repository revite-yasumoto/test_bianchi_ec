import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { CancelOrderButton } from '@/front/Components/MyPage/CancelOrderButton';
import { ProductVisual } from '@/front/Components/Product/ProductVisual';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';
import { dotDateTimeLabel } from '@/front/lib/date';
import { japaneseDateLabel } from '@/front/lib/delivery';
import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';
import { yen } from '@/shared/lib/yen';

type OrderDetailItem = {
    id: number;
    product_name: string;
    category_name: string;
    variant_label: string;
    product_image_url: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
};

type Props = {
    order: {
        id: number;
        order_number: string;
        /** ISO 8601 の日時 */
        ordered_at: string;
        status_label: string;
        status_tone: Tone;
        payment_method_label: string;
        /** 銀行振込のときのみ、注文時点のEC基本設定の案内文 */
        bank_transfer_note: string | null;
        estimated_delivery_date: string;
        items: OrderDetailItem[];
        subtotal: number;
        shipping_fee: number;
        cod_fee: number;
        total: number;
        shipping: {
            recipient_name: string;
            postal_code: string;
            prefecture_name: string;
            city: string;
            address_line1: string;
            address_line2: string | null;
            tel: string;
        };
        histories: {
            id: number;
            to_status_label: string;
            changed_at: string;
        }[];
        is_cancelable: boolean;
    };
};

const CARD_CLASS = 'rounded-2xl border border-line p-5';

const HEADING_CLASS = 'mb-3 text-[14px] font-extrabold';

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
            className={cn(
                'flex justify-between',
                emphasized
                    ? 'border-t border-line pt-2.5 text-[15px] font-extrabold'
                    : 'text-[12.5px] text-ink2',
            )}
        >
            <span>{label}</span>
            <span className="font-mono">{yen(amount)}</span>
        </div>
    );
}

export default function OrderShow({ order }: Props) {
    return (
        <MyPageLayout
            title={`注文 ${order.order_number}`}
            description="ご注文の内容・お届け先・お支払い方法とご注文の状況を確認できます。"
            heading={`注文 ${order.order_number}`}
        >
            <Link
                href={route('mypage.index')}
                className="mb-3.5 inline-block font-mono text-[11px] text-ink2"
            >
                ← 注文履歴へ
            </Link>

            <div className="flex flex-wrap items-center gap-2.5">
                <Badge tone={order.status_tone}>{order.status_label}</Badge>
                <time
                    dateTime={order.ordered_at}
                    className="text-[12px] text-ink2"
                >
                    {dotDateTimeLabel(order.ordered_at)}
                </time>
            </div>

            <div className="mt-4 grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <section className={CARD_CLASS}>
                    <h2 className={HEADING_CLASS}>ご注文商品</h2>
                    <ul className="border-t border-line">
                        {order.items.map((item) => (
                            <li
                                key={item.id}
                                className="flex gap-3.5 border-b border-line py-3.5"
                            >
                                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl">
                                    <ProductVisual
                                        imageUrl={item.product_image_url}
                                        categoryName={item.category_name}
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-[13px] leading-[1.5] font-bold">
                                        {item.product_name}
                                    </p>
                                    <p className="mt-1 text-[11.5px] text-ink2">
                                        {item.variant_label} ／{' '}
                                        {yen(item.unit_price)} × {item.quantity}
                                    </p>
                                </div>
                                <p className="font-mono text-[13px] font-bold">
                                    {yen(item.subtotal)}
                                </p>
                            </li>
                        ))}
                    </ul>

                    <div className="mt-3.5 flex flex-col gap-2">
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
                </section>

                <div className="flex flex-col gap-4">
                    <section className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>お届け先</h2>
                        <p className="text-[12.5px] leading-loose text-ink2">
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

                        <h2 className={cn(HEADING_CLASS, 'mt-4')}>
                            お支払い・配送
                        </h2>
                        <p className="text-[12.5px] leading-loose text-ink2">
                            {order.payment_method_label}
                            <br />
                            お届け予定日{' '}
                            <time dateTime={order.estimated_delivery_date}>
                                {japaneseDateLabel(
                                    order.estimated_delivery_date,
                                )}
                            </time>
                        </p>
                    </section>

                    {order.bank_transfer_note ? (
                        <section className={CARD_CLASS}>
                            <h2 className={HEADING_CLASS}>お振込みのご案内</h2>
                            <p className="text-[12.5px] leading-[1.9] whitespace-pre-line text-ink2">
                                {order.bank_transfer_note}
                            </p>
                        </section>
                    ) : null}

                    <section className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>ご注文の状況</h2>
                        <ol className="flex flex-col gap-2.5">
                            {order.histories.map((history) => (
                                <li
                                    key={history.id}
                                    className="flex justify-between gap-3 text-[12.5px]"
                                >
                                    <span className="font-bold">
                                        {history.to_status_label}
                                    </span>
                                    <time
                                        dateTime={history.changed_at}
                                        className="text-ink2"
                                    >
                                        {dotDateTimeLabel(history.changed_at)}
                                    </time>
                                </li>
                            ))}
                        </ol>
                    </section>

                    {order.is_cancelable ? (
                        <CancelOrderButton
                            orderId={order.id}
                            orderNumber={order.order_number}
                            className="self-start"
                        />
                    ) : null}
                </div>
            </div>
        </MyPageLayout>
    );
}
