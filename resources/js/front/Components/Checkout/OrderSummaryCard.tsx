import type { ReactNode } from 'react';
import type { CartRow } from '@/front/Components/Cart/CartItemRow';
import { yen } from '@/shared/lib/yen';

type OrderSummaryCardProps = {
    title: string;
    /** 明細を出さない画面（注文確認は左カラムに明細を置く）では省略する */
    items?: CartRow[];
    subtotal: number;
    shippingFee: number;
    codFee: number;
    total: number;
    prefectureName: string;
    deliveryDateLabel: string;
    children: ReactNode;
};

export function OrderSummaryCard({
    title,
    items,
    subtotal,
    shippingFee,
    codFee,
    total,
    prefectureName,
    deliveryDateLabel,
    children,
}: OrderSummaryCardProps) {
    return (
        <aside className="rounded-[20px] bg-bg2 p-6">
            <h2 className="mb-3.5 text-[15px] font-extrabold">{title}</h2>

            {items ? (
                <ul className="mb-3 border-b border-line pb-3">
                    {items.map((item) => (
                        <li
                            key={item.id}
                            className="flex gap-2.5 py-2 text-[12.5px]"
                        >
                            <span className="flex-1 leading-[1.5]">
                                {item.product_name}
                                <span className="text-ink2">
                                    {' '}
                                    ×{item.quantity}
                                </span>
                            </span>
                            <span className="font-mono font-bold">
                                {yen(item.subtotal)}
                            </span>
                        </li>
                    ))}
                </ul>
            ) : null}

            <dl>
                <div className="flex justify-between py-1.5 text-[13.5px]">
                    <dt>商品合計</dt>
                    <dd className="font-mono font-bold">{yen(subtotal)}</dd>
                </div>
                <div className="flex justify-between py-1.5 text-[13.5px]">
                    <dt>送料（{prefectureName}）</dt>
                    <dd className="font-mono font-bold">
                        {shippingFee === 0 ? '無料' : yen(shippingFee)}
                    </dd>
                </div>
                <div className="flex justify-between py-1.5 text-[13.5px]">
                    <dt>代引き手数料</dt>
                    <dd className="font-mono font-bold">
                        {codFee === 0 ? '—' : yen(codFee)}
                    </dd>
                </div>
                <div className="mt-2 flex justify-between border-t border-line pt-3.5 pb-1 text-lg font-extrabold">
                    <dt>合計</dt>
                    <dd className="font-mono">{yen(total)}</dd>
                </div>
            </dl>

            <p className="mt-3.5 rounded-xl border border-line bg-white px-4 py-3.5">
                <span className="block text-[11.5px] font-bold text-ink2">
                    お届け予定日
                </span>
                <span className="mt-1 block text-[17px] font-extrabold text-brand">
                    {deliveryDateLabel}
                </span>
            </p>

            <div className="mt-4.5 flex flex-col gap-2.5">{children}</div>
        </aside>
    );
}
