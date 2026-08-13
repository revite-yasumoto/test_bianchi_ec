import { Link, useForm } from '@inertiajs/react';
import type { CartRow } from '@/front/Components/Cart/CartItemRow';
import { addressLines } from '@/front/Components/Checkout/AddressSelector';
import type { AddressData } from '@/front/Components/Checkout/AddressSelector';
import { OrderSummaryCard } from '@/front/Components/Checkout/OrderSummaryCard';
import { StepIndicator } from '@/front/Components/Checkout/StepIndicator';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { japaneseDateLabel } from '@/front/lib/delivery';
import { categoryTint } from '@/front/lib/tint';
import { PaymentMethod } from '@/shared/lib/enums';
import { yen } from '@/shared/lib/yen';

type Props = {
    items: CartRow[];
    address: AddressData;
    paymentMethod: PaymentMethod;
    amounts: {
        subtotal: number;
        shipping_fee: number;
        cod_fee: number;
        total: number;
        /** ISO 8601 の日付 */
        estimated_delivery_date: string;
    };
    /** 銀行振込のときのみ、EC基本設定の案内文 */
    bankTransferNote: string | null;
};

export default function Confirm({
    items,
    address,
    paymentMethod,
    amounts,
    bankTransferNote,
}: Props) {
    const { post, processing } = useForm({});
    const isCod = paymentMethod === PaymentMethod.Cod;

    return (
        <FrontLayout
            title="注文内容の確認"
            description="お届け先・お支払い方法・商品明細と最終合計金額をご確認ください。"
        >
            <div className="px-[22px] py-[26px] pb-14">
                <StepIndicator current={3} />
                <h1 className="mb-5.5 text-[26px] font-black">
                    注文内容の確認
                </h1>

                <div className="grid items-start gap-6.5 lg:grid-cols-[minmax(320px,1fr)_minmax(320px,1fr)]">
                    <div className="flex flex-col gap-4.5">
                        <section className="rounded-2xl border border-line p-5">
                            <h2 className="mb-2 text-xs font-bold text-ink2">
                                お届け先
                            </h2>
                            <p className="text-sm font-bold">
                                {address.recipient_name}（{address.label}）
                            </p>
                            <p className="mt-1.5 text-[13px] leading-[1.7] text-ink2">
                                {addressLines(address)}
                            </p>
                        </section>

                        <section className="rounded-2xl border border-line p-5">
                            <h2 className="mb-2 text-xs font-bold text-ink2">
                                お支払い方法
                            </h2>
                            <p className="text-sm font-bold">
                                {isCod ? '代金引換' : '銀行振込（前払い）'}
                            </p>
                            <p className="mt-1.5 text-[13px] leading-[1.7] whitespace-pre-line text-ink2">
                                {isCod
                                    ? '商品到着時に配達員へお支払いください。'
                                    : bankTransferNote}
                            </p>
                        </section>

                        <section className="rounded-2xl border border-line p-5">
                            <h2 className="mb-3 text-xs font-bold text-ink2">
                                商品明細
                            </h2>
                            <ul>
                                {items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex gap-3 border-t border-line py-2.5"
                                    >
                                        <span
                                            className="h-[52px] w-[52px] shrink-0 overflow-hidden rounded-[9px]"
                                            style={{
                                                backgroundImage: categoryTint(
                                                    item.category_name,
                                                ),
                                            }}
                                        >
                                            {item.main_image_url ? (
                                                <img
                                                    src={item.main_image_url}
                                                    alt=""
                                                    loading="lazy"
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : null}
                                        </span>
                                        <span className="flex-1">
                                            <span className="block text-[13px] leading-[1.45] font-bold">
                                                {item.product_name}
                                            </span>
                                            <span className="mt-1 block text-[11.5px] text-ink2">
                                                {item.variant_label} / 数量{' '}
                                                {item.quantity}
                                            </span>
                                        </span>
                                        <span className="font-mono text-[13.5px] font-bold">
                                            {yen(item.subtotal)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    </div>

                    <OrderSummaryCard
                        title="お支払い金額"
                        subtotal={amounts.subtotal}
                        shippingFee={amounts.shipping_fee}
                        codFee={amounts.cod_fee}
                        total={amounts.total}
                        prefectureName={address.prefecture_name}
                        deliveryDateLabel={japaneseDateLabel(
                            amounts.estimated_delivery_date,
                        )}
                    >
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => post(route('orders.store'))}
                            className="w-full rounded-full bg-coral py-4.5 text-base font-extrabold text-white disabled:opacity-40"
                        >
                            注文を確定する
                        </button>
                        <Link
                            href={route('checkout.index')}
                            className="w-full rounded-full border border-line py-3.5 text-center text-[13.5px] font-bold"
                        >
                            購入手続きに戻る
                        </Link>
                    </OrderSummaryCard>
                </div>
            </div>
        </FrontLayout>
    );
}
