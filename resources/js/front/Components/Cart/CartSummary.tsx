import { Link } from '@inertiajs/react';
import { yen } from '@/shared/lib/yen';
import { FreeShippingProgress } from './FreeShippingProgress';

type CartSummaryProps = {
    subtotal: number;
    estimatedShippingLabel: string;
    estimatedTotal: number;
    freeShippingThreshold: number;
    remainingForFreeShipping: number;
    estimatedPrefectureName: string;
    canCheckout: boolean;
};

export function CartSummary({
    subtotal,
    estimatedShippingLabel,
    estimatedTotal,
    freeShippingThreshold,
    remainingForFreeShipping,
    estimatedPrefectureName,
    canCheckout,
}: CartSummaryProps) {
    // 購入手続き画面は単位16で実装するため、それまでは非活性で表示する
    const isCheckoutReady = canCheckout && route().has('checkout.index');

    return (
        <aside className="rounded-[20px] bg-bg2 p-6">
            <h2 className="mb-4 text-[15px] font-extrabold">お支払い金額</h2>

            <dl>
                <div className="flex justify-between py-1.5 text-[13.5px]">
                    <dt>商品合計</dt>
                    <dd className="font-mono font-bold">{yen(subtotal)}</dd>
                </div>
                <div className="flex justify-between py-1.5 text-[13.5px]">
                    <dt>概算送料（{estimatedPrefectureName}）</dt>
                    <dd className="font-mono font-bold">
                        {estimatedShippingLabel}
                    </dd>
                </div>
                <div className="mt-2 flex justify-between border-t border-line py-3.5 text-base font-extrabold">
                    <dt>合計</dt>
                    <dd className="font-mono">{yen(estimatedTotal)}</dd>
                </div>
            </dl>

            <div className="mt-2.5">
                <FreeShippingProgress
                    remaining={remainingForFreeShipping}
                    threshold={freeShippingThreshold}
                />
            </div>

            {!canCheckout ? (
                <p
                    role="alert"
                    className="mt-4 text-[12.5px] font-bold text-coral"
                >
                    在庫が不足している商品があります。数量を変更するか、その商品を削除してください。
                </p>
            ) : null}

            {isCheckoutReady ? (
                <Link
                    href={route('checkout.index')}
                    className="mt-5 block w-full rounded-full bg-coral py-4 text-center text-[15px] font-extrabold text-white"
                >
                    購入手続きへ進む
                </Link>
            ) : (
                <span
                    aria-disabled="true"
                    className="mt-5 block w-full cursor-not-allowed rounded-full bg-coral py-4 text-center text-[15px] font-extrabold text-white opacity-40"
                >
                    購入手続きへ進む
                </span>
            )}

            <Link
                href={route('products.index')}
                className="mt-2.5 block w-full rounded-full border border-line py-3.5 text-center text-[13.5px] font-bold"
            >
                買い物を続ける
            </Link>

            <p className="mt-3 text-[11.5px] leading-[1.6] text-ink2">
                送料は配送先の都道府県で確定します。購入手続きで配送先を選ぶと再計算されます。
            </p>
        </aside>
    );
}
