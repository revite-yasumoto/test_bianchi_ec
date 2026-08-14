import { Link } from '@inertiajs/react';
import { Pagination } from '@/front/Components/Pagination';
import { CategoryChips } from '@/front/Components/Product/CategoryChips';
import { ProductCard } from '@/front/Components/Product/ProductCard';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { EmptyState } from '@/shared/Components/EmptyState';

type Props = {
    products: Paginated<ProductCardData>;
    categories: { id: number; name: string }[];
    filters: { category_id: number | null };
    totalCount: number;
};

export default function Index({
    products,
    categories,
    filters,
    totalCount,
}: Props) {
    const isFiltered = filters.category_id !== null;

    return (
        <FrontLayout
            title="商品一覧"
            description="Bianchi オンラインストアの取り扱い商品一覧です。"
        >
            <div className="px-5 py-6 pb-14">
                <nav aria-label="パンくず" className="mb-2.5">
                    <ol className="flex gap-1 font-mono text-[11px] text-ink2">
                        <li>
                            <Link href={route('top')}>HOME</Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">PRODUCTS</li>
                    </ol>
                </nav>

                <h1 className="mb-1.5 text-[28px] font-black">商品一覧</h1>
                <p className="mb-5 text-[13px] text-ink2">
                    {products.total}件の商品
                    {isFiltered ? ` / 全${totalCount}件` : ''}
                </p>

                <div className="mb-5">
                    <CategoryChips
                        categories={categories}
                        selectedId={filters.category_id}
                    />
                </div>

                {products.data.length > 0 ? (
                    <div className="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-4">
                        {products.data.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                ) : (
                    <EmptyState message="該当する商品はありません" />
                )}

                <Pagination links={products.links} />
            </div>
        </FrontLayout>
    );
}
