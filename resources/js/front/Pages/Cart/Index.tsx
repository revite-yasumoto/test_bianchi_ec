import { Link } from '@inertiajs/react';
import { CartItemRow } from '@/front/Components/Cart/CartItemRow';
import type { CartRow } from '@/front/Components/Cart/CartItemRow';
import { CartSummary } from '@/front/Components/Cart/CartSummary';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

type Props = {
    items: CartRow[];
    subtotal: number;
    estimatedShippingLabel: string;
    estimatedTotal: number;
    freeShippingThreshold: number;
    remainingForFreeShipping: number;
    estimatedPrefectureName: string;
};

export default function Index({
    items,
    subtotal,
    estimatedShippingLabel,
    estimatedTotal,
    freeShippingThreshold,
    remainingForFreeShipping,
    estimatedPrefectureName,
}: Props) {
    const canCheckout =
        items.length > 0 && items.every((item) => item.is_purchasable);

    return (
        <FrontLayout
            title="カート"
            description="カートに入れた商品の確認・数量変更ができます。"
        >
            <div className="px-[22px] py-[26px] pb-14">
                <h1 className="mb-5 text-[26px] font-black">カート</h1>

                {items.length === 0 ? (
                    <div className="rounded-[20px] bg-bg2 px-5 py-[60px] text-center">
                        <p className="mb-3.5 text-[15px] font-bold">
                            カートに商品がありません
                        </p>
                        <Link
                            href={route('products.index')}
                            className="inline-block rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white"
                        >
                            商品を探す
                        </Link>
                    </div>
                ) : (
                    <div className="grid items-start gap-6.5 lg:grid-cols-[minmax(300px,1fr)_minmax(300px,1fr)]">
                        <ul className="border-t border-line">
                            {items.map((item) => (
                                <CartItemRow key={item.id} row={item} />
                            ))}
                        </ul>
                        <CartSummary
                            subtotal={subtotal}
                            estimatedShippingLabel={estimatedShippingLabel}
                            estimatedTotal={estimatedTotal}
                            freeShippingThreshold={freeShippingThreshold}
                            remainingForFreeShipping={remainingForFreeShipping}
                            estimatedPrefectureName={estimatedPrefectureName}
                            canCheckout={canCheckout}
                        />
                    </div>
                )}
            </div>
        </FrontLayout>
    );
}
