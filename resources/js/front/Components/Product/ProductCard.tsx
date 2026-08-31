import { Link } from '@inertiajs/react';
import { ProductVisual } from '@/front/Components/Product/ProductVisual';
import { yen } from '@/shared/lib/yen';

/** 商品一覧・TOP・お気に入りで共用する商品カードの表示データ */
export type ProductCardData = {
    id: number;
    name: string;
    category_name: string;
    product_code: string;
    price: number;
    main_image_url: string | null;
    is_sold_out: boolean;
};

type ProductCardProps = {
    product: ProductCardData;
    /** ランキングで表示する順位。指定すると画像左上に順位バッジを重ねる */
    rank?: number;
};

export function ProductCard({ product, rank }: ProductCardProps) {
    return (
        <Link
            href={route('products.show', [product.id])}
            className="block overflow-hidden rounded-[18px] border border-line bg-white"
        >
            <div className="relative aspect-4/3">
                <ProductVisual
                    imageUrl={product.main_image_url}
                    categoryName={product.category_name}
                    productCode={product.product_code}
                />
                {rank !== undefined ? (
                    <span className="absolute top-2.5 left-2.5 flex h-6.5 w-6.5 items-center justify-center rounded-full bg-white font-mono text-sm font-bold text-ink">
                        <span className="sr-only">第{rank}位</span>
                        <span aria-hidden="true">{rank}</span>
                    </span>
                ) : null}
                {product.is_sold_out ? (
                    <span className="absolute top-2.5 right-2.5 rounded-full bg-ink/80 px-2.5 py-1 text-sm font-bold text-white">
                        在庫切れ
                    </span>
                ) : null}
            </div>
            <div className="px-4 pt-3.5 pb-4">
                <p className="truncate text-sm text-ink2">
                    {product.category_name}
                </p>
                {/* 商品名は255文字まで登録できるため、行数を固定して並べたカードの高さを揃える */}
                <p className="mt-1 line-clamp-2 min-h-[2.9em] text-base leading-[1.45] font-bold">
                    {product.name}
                </p>
                <p className="mt-2 font-mono text-base font-bold">
                    {yen(product.price)}
                </p>
            </div>
        </Link>
    );
}
