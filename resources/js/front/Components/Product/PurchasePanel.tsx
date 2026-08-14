import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useCartDrawer } from '@/front/Layouts/FrontLayout';
import { useVariantSelection } from '@/front/hooks/useVariantSelection';
import type { VariantData } from '@/front/hooks/useVariantSelection';
import { cn } from '@/lib/utils';
import { yen } from '@/shared/lib/yen';
import { FavoriteButton } from './FavoriteButton';
import { StockBadge } from './StockBadge';
import { VariantSelector } from './VariantSelector';

export type ProductDetailData = {
    id: number;
    product_code: string;
    name: string;
    category_name: string;
    price: number;
    description: string | null;
    has_sku: boolean;
    is_sold_out: boolean;
    images: { url: string; sort_order: number }[];
    specs: { label: string; value: string }[];
    variants: VariantData[];
    sizes: string[];
    colors: string[];
};

type PurchasePanelProps = {
    product: ProductDetailData;
    isFavorited: boolean;
    onOpenShippingInfo: () => void;
};

export function PurchasePanel({
    product,
    isFavorited,
    onOpenShippingInfo,
}: PurchasePanelProps) {
    const { openCart } = useCartDrawer();
    const {
        colorOptions,
        sizeOptions,
        selectColor,
        selectSize,
        isSelectionComplete,
        selectedVariant,
    } = useVariantSelection(product);
    const [showSelectionWarning, setShowSelectionWarning] = useState(false);
    const form = useForm({ product_variant_id: 0, quantity: 1 });

    const addToCart = () => {
        if (!isSelectionComplete || !selectedVariant) {
            setShowSelectionWarning(true);

            return;
        }

        setShowSelectionWarning(false);
        form.transform(() => ({
            product_variant_id: selectedVariant.id,
            quantity: 1,
        }));
        form.post(route('cart.items.store'), {
            preserveScroll: true,
            onSuccess: () => openCart(),
        });
    };

    return (
        <div>
            <p className="text-xs text-ink2">{product.category_name}</p>
            <h1 className="mt-1.5 text-[27px] leading-[1.35] font-black">
                {product.name}
            </h1>
            <p className="mt-2 font-mono text-xs text-ink2">
                商品ID {product.product_code}
            </p>

            <p className="mt-4 flex items-baseline gap-2.5">
                <span className="font-mono text-[30px] font-bold">
                    {yen(product.price)}
                </span>
                <span className="text-xs text-ink2">税込</span>
            </p>

            <div className="mt-3.5">
                <StockBadge inStock={!product.is_sold_out} />
            </div>

            {product.has_sku ? (
                <div className="mt-6 flex flex-col gap-5">
                    <VariantSelector
                        label="カラー"
                        options={colorOptions}
                        onSelect={selectColor}
                    />
                    <VariantSelector
                        label="サイズ"
                        options={sizeOptions}
                        onSelect={selectSize}
                        minWidthClassName="min-w-[52px]"
                    />
                    <p className="font-mono text-[11px] text-ink2">
                        SKU: {selectedVariant?.sku_code ?? '— 選択してください'}
                    </p>
                </div>
            ) : null}

            {showSelectionWarning ? (
                <p
                    role="alert"
                    className="mt-3.5 text-[12.5px] font-bold text-coral"
                >
                    カラーとサイズを選択してください
                </p>
            ) : null}

            {form.errors.product_variant_id ? (
                <p
                    role="alert"
                    className="mt-3.5 text-[12.5px] font-bold text-coral"
                >
                    {form.errors.product_variant_id}
                </p>
            ) : null}
            {form.errors.quantity ? (
                <p
                    role="alert"
                    className="mt-3.5 text-[12.5px] font-bold text-coral"
                >
                    {form.errors.quantity}
                </p>
            ) : null}

            <div className="mt-6 flex flex-wrap gap-2.5">
                <button
                    type="button"
                    disabled={product.is_sold_out || form.processing}
                    onClick={addToCart}
                    className={cn(
                        'min-w-[200px] flex-1 rounded-full px-6 py-4 text-[15px] font-extrabold text-white',
                        product.is_sold_out
                            ? 'cursor-not-allowed bg-[#C4C8CC]'
                            : 'bg-coral hover:bg-coral/90',
                    )}
                >
                    {product.is_sold_out ? '在庫切れ' : 'カートに入れる'}
                </button>
                <FavoriteButton
                    productId={product.id}
                    isFavorited={isFavorited}
                />
            </div>

            <div className="mt-5 flex flex-col gap-2.5 border-t border-line pt-5">
                <button
                    type="button"
                    onClick={onOpenShippingInfo}
                    className="text-left text-[13px] font-semibold text-brand"
                >
                    送料・お支払い方法・発送日数について →
                </button>
                <Link
                    href={route('contact', { product_name: product.name })}
                    className="text-left text-[13px] font-semibold text-brand"
                >
                    この商品について問い合わせる →
                </Link>
            </div>
        </div>
    );
}
