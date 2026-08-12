import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { DetailTabs } from '@/front/Components/Product/DetailTabs';
import { ImageGallery } from '@/front/Components/Product/ImageGallery';
import { PurchasePanel } from '@/front/Components/Product/PurchasePanel';
import type { ProductDetailData } from '@/front/Components/Product/PurchasePanel';
import { ShippingInfoModal } from '@/front/Components/Product/ShippingInfoModal';
import type { ShippingTableRow } from '@/front/Components/Product/ShippingInfoModal';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { categoryTint } from '@/front/lib/tint';

type Props = {
    product: ProductDetailData;
    isFavorited: boolean;
    shippingTable: ShippingTableRow[];
    ecSetting: { free_shipping_threshold: number; cod_fee: number };
};

export default function Show({
    product,
    isFavorited,
    shippingTable,
    ecSetting,
}: Props) {
    const [isShippingInfoOpen, setIsShippingInfoOpen] = useState(false);

    return (
        <FrontLayout
            title={product.name}
            description={`${product.name}（${product.category_name}）の商品詳細です。`}
        >
            <div className="px-5 py-5 pb-14">
                <Link
                    href={route('products.index')}
                    className="font-mono text-[11px] text-ink2"
                >
                    ← 商品一覧へ戻る
                </Link>

                <div className="mt-4 grid items-start gap-8 lg:grid-cols-2">
                    <ImageGallery
                        images={product.images}
                        productName={product.name}
                        productCode={product.product_code}
                        tint={categoryTint(product.category_name)}
                    />
                    <PurchasePanel
                        product={product}
                        isFavorited={isFavorited}
                        onOpenShippingInfo={() => setIsShippingInfoOpen(true)}
                    />
                </div>

                <DetailTabs
                    description={product.description}
                    specs={product.specs}
                />
            </div>

            <ShippingInfoModal
                isOpen={isShippingInfoOpen}
                onClose={() => setIsShippingInfoOpen(false)}
                shippingTable={shippingTable}
                ecSetting={ecSetting}
            />
        </FrontLayout>
    );
}
