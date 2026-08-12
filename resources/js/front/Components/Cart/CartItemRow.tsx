import { Link } from '@inertiajs/react';
import { useCartItem } from '@/front/hooks/useCartItem';
import { categoryTint } from '@/front/lib/tint';
import { yen } from '@/shared/lib/yen';
import { QuantityStepper } from './QuantityStepper';

export type CartRow = {
    id: number;
    product_id: number;
    product_name: string;
    category_name: string;
    variant_label: string;
    main_image_url: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
    in_stock: boolean;
    is_purchasable: boolean;
    max_quantity: number;
};

/** 購入できない行に出す理由。在庫の実数は出さず、二値表示の原則を守る */
function unavailableMessage(row: CartRow) {
    if (row.is_purchasable) {
        return null;
    }

    if (!row.in_stock) {
        return '在庫切れのため購入手続きに進めません';
    }

    if (row.quantity > row.max_quantity) {
        return '在庫が不足しています。数量を減らしてください';
    }

    return '現在お取り扱いできません';
}

type CartItemRowProps = {
    row: CartRow;
};

export function CartItemRow({ row }: CartItemRowProps) {
    const { changeQuantity, remove, isProcessing, error } = useCartItem(
        row.id,
        row.quantity,
    );
    const message = unavailableMessage(row);

    return (
        <li className="flex flex-wrap gap-3.5 border-b border-line px-0.5 py-4.5">
            <div
                className="h-[86px] w-[86px] shrink-0 overflow-hidden rounded-xl"
                style={{ backgroundImage: categoryTint(row.category_name) }}
            >
                {row.main_image_url ? (
                    <img
                        src={row.main_image_url}
                        alt=""
                        loading="lazy"
                        className="h-full w-full object-cover"
                    />
                ) : null}
            </div>

            <div className="min-w-[150px] flex-1">
                <Link
                    href={route('products.show', [row.product_id])}
                    className="text-sm leading-[1.45] font-bold"
                >
                    {row.product_name}
                </Link>
                <p className="mt-1 text-[11.5px] text-ink2">
                    {row.variant_label}
                </p>
                <p className="mt-2 font-mono text-sm font-bold">
                    {yen(row.unit_price)}
                </p>

                <div className="mt-2.5 flex items-center gap-3.5">
                    <QuantityStepper
                        quantity={row.quantity}
                        max={row.max_quantity}
                        disabled={isProcessing}
                        onChange={changeQuantity}
                    />
                    <button
                        type="button"
                        onClick={remove}
                        className="text-xs text-ink2 underline"
                    >
                        削除
                    </button>
                </div>

                {message ? (
                    <p className="mt-2.5 text-[12px] font-bold text-coral">
                        {message}
                    </p>
                ) : null}
                {error ? (
                    <p
                        role="alert"
                        className="mt-2.5 text-[12px] font-bold text-coral"
                    >
                        {error}
                    </p>
                ) : null}
            </div>

            <p className="ml-auto font-mono text-[15px] font-bold">
                {yen(row.subtotal)}
            </p>
        </li>
    );
}
