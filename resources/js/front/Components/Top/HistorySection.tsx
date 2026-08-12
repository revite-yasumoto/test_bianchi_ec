import { Link } from '@inertiajs/react';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';
import { categoryTint } from '@/front/lib/tint';
import { yen } from '@/shared/lib/yen';

type HistorySectionProps = {
    products: ProductCardData[];
};

/** ログイン中の会員にだけ出す「最近見た商品」。横スクロールの小カードで並べる */
export function HistorySection({ products }: HistorySectionProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="px-5 pt-10 pb-2">
            <h2 className="mb-3.5 text-xl font-black">最近見た商品</h2>
            <ul className="flex gap-3 overflow-x-auto pb-1.5">
                {products.map((product) => (
                    <li key={product.id} className="flex-none">
                        <Link
                            href={route('products.show', [product.id])}
                            className="block w-[148px] overflow-hidden rounded-[14px] border border-line bg-white"
                        >
                            <div
                                className="h-20"
                                style={{
                                    backgroundImage: categoryTint(
                                        product.category_name,
                                    ),
                                }}
                            >
                                {product.main_image_url ? (
                                    <img
                                        src={product.main_image_url}
                                        alt=""
                                        loading="lazy"
                                        className="h-full w-full object-cover"
                                    />
                                ) : null}
                            </div>
                            <div className="px-3 pt-2.5 pb-3">
                                <p className="text-xs leading-[1.4] font-bold">
                                    {product.name}
                                </p>
                                <p className="mt-1 font-mono text-xs">
                                    {yen(product.price)}
                                </p>
                            </div>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
