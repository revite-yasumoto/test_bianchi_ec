import { ProductCard } from '@/front/Components/Product/ProductCard';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';

type HistorySectionProps = {
    products: ProductCardData[];
};

/** ログイン中の会員にだけ出す「最近見た商品」。ランキング・おすすめと同じ商品カードを横スクロールで並べる */
export function HistorySection({ products }: HistorySectionProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="px-5 pt-10 pb-2">
            <h2 className="mb-3.5 text-xl font-black">最近見た商品</h2>
            <ul className="flex gap-3.5 overflow-x-auto pb-1.5">
                {products.map((product) => (
                    <li key={product.id} className="w-[190px] flex-none">
                        <ProductCard product={product} />
                    </li>
                ))}
            </ul>
        </section>
    );
}
