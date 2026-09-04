import { Link } from '@inertiajs/react';
import { Pagination } from '@/front/Components/Pagination';
import { FilterChips } from '@/front/Components/Product/FilterChips';
import { InStockCheckbox } from '@/front/Components/Product/InStockCheckbox';
import { ProductCard } from '@/front/Components/Product/ProductCard';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';
import { SortSelect } from '@/front/Components/Product/SortSelect';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import {
    productIndexQuery,
    type ProductIndexFilters,
} from '@/front/lib/productIndexQuery';
import { EmptyState } from '@/shared/Components/EmptyState';
import type { PriceRange, ProductSort } from '@/shared/lib/enums';

type Props = {
    products: Paginated<ProductCardData>;
    categories: { id: number; name: string }[];
    priceRanges: { value: PriceRange; label: string }[];
    sorts: { value: ProductSort; label: string }[];
    filters: ProductIndexFilters;
    totalCount: number;
};

export default function Index({
    products,
    categories,
    priceRanges,
    sorts,
    filters,
    totalCount,
}: Props) {
    const isFiltered =
        filters.category_id !== null ||
        filters.price_range !== null ||
        filters.in_stock;

    return (
        <FrontLayout
            title="商品一覧"
            description="Bianchi オンラインストアの取り扱い商品一覧です。"
        >
            <div className="px-5 py-6 pb-14">
                <nav aria-label="パンくず" className="mb-2.5">
                    <ol className="flex gap-1 font-mono text-sm text-ink2">
                        <li>
                            <Link href={route('top')}>HOME</Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">PRODUCTS</li>
                    </ol>
                </nav>

                <h1 className="mb-1.5 text-[28px] font-black">商品一覧</h1>

                <div className="mb-5 flex flex-wrap items-center justify-between gap-2.5">
                    <p className="text-[15px] text-ink2">
                        {products.total}件の商品
                        {isFiltered ? ` / 全${totalCount}件` : ''}
                    </p>
                    <SortSelect sorts={sorts} filters={filters} />
                </div>

                <div className="mb-5 flex flex-col gap-2.5">
                    <FilterChips
                        label="カテゴリ"
                        options={categories.map((category) => ({
                            value: category.id,
                            label: category.name,
                        }))}
                        selected={filters.category_id}
                        buildHref={(value) =>
                            route(
                                'products.index',
                                productIndexQuery(filters, {
                                    category_id: value,
                                }),
                            )
                        }
                    />
                    <FilterChips
                        label="価格帯"
                        options={priceRanges}
                        selected={filters.price_range}
                        buildHref={(value) =>
                            route(
                                'products.index',
                                productIndexQuery(filters, {
                                    price_range: value,
                                }),
                            )
                        }
                    />
                    <InStockCheckbox filters={filters} />
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
