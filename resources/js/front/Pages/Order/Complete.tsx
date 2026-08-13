import { Link } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { japaneseDateLabel } from '@/front/lib/delivery';
import { PaymentMethod } from '@/shared/lib/enums';

type Props = {
    order: {
        order_number: string;
        /** ISO 8601 の日付 */
        estimated_delivery_date: string;
        payment_method: PaymentMethod;
        /** 銀行振込のときのみ、注文時点のEC基本設定の案内文 */
        bank_transfer_note: string | null;
    };
};

export default function Complete({ order }: Props) {
    const isCod = order.payment_method === PaymentMethod.Cod;

    return (
        <FrontLayout title="ご注文完了">
            <div className="flex justify-center px-[22px] pt-[70px] pb-[90px]">
                <div className="w-full max-w-[520px] text-center">
                    <p
                        aria-hidden="true"
                        className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-teal text-[28px] text-white"
                    >
                        ✓
                    </p>
                    <h1 className="mt-5.5 text-[26px] font-black">
                        ご注文ありがとうございました
                    </h1>
                    <p className="mt-3 text-[13.5px] leading-[1.9] text-ink2">
                        {isCod
                            ? 'ご注文を受け付けました。出荷準備が整い次第、発送のご案内をお送りします。'
                            : 'ご注文を受け付けました。ステータスは「入金待ち」です。ご入金確認後に発送準備を開始します。'}
                    </p>

                    <div className="mt-6.5 rounded-2xl bg-bg2 p-5.5">
                        <p className="text-[11.5px] font-bold text-ink2">
                            注文番号
                        </p>
                        <p className="mt-1.5 font-mono text-[22px] font-bold">
                            {order.order_number}
                        </p>
                        <p className="mt-3 text-[12.5px] text-ink2">
                            お届け予定日{' '}
                            <time dateTime={order.estimated_delivery_date}>
                                {japaneseDateLabel(
                                    order.estimated_delivery_date,
                                )}
                            </time>
                        </p>
                    </div>

                    {order.bank_transfer_note ? (
                        <section className="mt-4 rounded-2xl border border-line p-5 text-left">
                            <h2 className="text-[13px] font-extrabold">
                                お振込みのご案内
                            </h2>
                            <p className="mt-2 text-[12.5px] leading-[1.9] whitespace-pre-line text-ink2">
                                {order.bank_transfer_note}
                            </p>
                        </section>
                    ) : null}

                    <div className="mt-6.5 flex flex-wrap justify-center gap-2.5">
                        {/* 注文履歴（マイページ）は単位17で実装するため、それまでは非活性で表示する */}
                        {route().has('orders.index') ? (
                            <Link
                                href={route('orders.index')}
                                className="rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white"
                            >
                                注文履歴を見る
                            </Link>
                        ) : (
                            <span
                                aria-disabled="true"
                                className="cursor-not-allowed rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white opacity-40"
                            >
                                注文履歴を見る
                            </span>
                        )}
                        <Link
                            href={route('top')}
                            className="rounded-full border border-line px-6.5 py-3.5 text-sm font-bold"
                        >
                            TOPへ戻る
                        </Link>
                    </div>
                </div>
            </div>
        </FrontLayout>
    );
}
