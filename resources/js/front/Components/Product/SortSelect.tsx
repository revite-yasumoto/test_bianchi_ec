import { router } from '@inertiajs/react';
import {
    productIndexQuery,
    type ProductIndexFilters,
} from '@/front/lib/productIndexQuery';
import type { ProductSort } from '@/shared/lib/enums';

type SortSelectProps = {
    sorts: { value: ProductSort; label: string }[];
    filters: ProductIndexFilters;
};

export function SortSelect({ sorts, filters }: SortSelectProps) {
    return (
        <div className="flex items-center gap-2">
            <label
                htmlFor="product-sort"
                className="text-[13px] font-bold text-ink2"
            >
                並び替え
            </label>
            <select
                id="product-sort"
                value={filters.sort}
                onChange={(event) =>
                    router.get(
                        route('products.index'),
                        productIndexQuery(filters, {
                            sort: event.target.value as ProductSort,
                        }),
                        { preserveScroll: true },
                    )
                }
                className="rounded-lg border border-line bg-white px-3 py-2 text-base font-bold text-ink"
            >
                {sorts.map((sort) => (
                    <option key={sort.value} value={sort.value}>
                        {sort.label}
                    </option>
                ))}
            </select>
        </div>
    );
}
