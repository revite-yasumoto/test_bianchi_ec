import { Link, router } from '@inertiajs/react';
import { ProductCard } from '@/front/Components/Product/ProductCard';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';

type Props = {
    products: ProductCardData[];
};

export default function Favorites({ products }: Props) {
    const remove = (productId: number) => {
        router.delete(route('favorites.destroy', [productId]), {
            preserveScroll: true,
        });
    };

    return (
        <MyPageLayout
            title="お気に入り"
            description="お気に入りに登録した商品の一覧です。"
            heading="お気に入り"
        >
            {products.length === 0 ? (
                <div className="rounded-[20px] bg-bg2 px-5 py-[60px] text-center">
                    <p className="mb-3.5 text-[15px] font-bold">
                        お気に入りに登録した商品はありません
                    </p>
                    <Link
                        href={route('products.index')}
                        className="inline-block rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white"
                    >
                        商品を探す
                    </Link>
                </div>
            ) : (
                <ul className="grid grid-cols-2 gap-3.5 lg:grid-cols-3">
                    {products.map((product) => (
                        <li key={product.id} className="flex flex-col gap-2">
                            <ProductCard product={product} />
                            <button
                                type="button"
                                onClick={() => remove(product.id)}
                                className="rounded-full border border-line py-2 text-xs font-bold text-coral"
                            >
                                お気に入りから外す
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </MyPageLayout>
    );
}
