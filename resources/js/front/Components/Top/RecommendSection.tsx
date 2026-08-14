import { ProductCard } from '@/front/Components/Product/ProductCard';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';

type RecommendSectionProps = {
    products: ProductCardData[];
};

export function RecommendSection({ products }: RecommendSectionProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="px-5 pt-10 pb-2">
            <h2 className="mb-4.5 text-2xl font-black">おすすめ</h2>
            <ul className="grid grid-cols-[repeat(auto-fill,minmax(190px,1fr))] gap-3.5">
                {products.map((product) => (
                    <li key={product.id}>
                        <ProductCard product={product} />
                    </li>
                ))}
            </ul>
        </section>
    );
}
