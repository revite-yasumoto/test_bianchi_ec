import type { PriceRange, ProductSort, StockFilter } from '@/shared/lib/enums';
import { ProductSort as ProductSortValue } from '@/shared/lib/enums';

export type ProductIndexFilters = {
    category_id: number | null;
    price_range: PriceRange | null;
    stock: StockFilter | null;
    sort: ProductSort;
};

type ProductIndexQuery = {
    category_id?: number;
    price_range?: PriceRange;
    stock?: StockFilter;
    sort?: ProductSort;
};

/**
 * 現在の絞り込み・並び替えから、指定した軸だけを差し替えたクエリを組み立てる。
 * 既定値（各軸の「すべて」・新着順）は付けない。ページ番号を引き継がないのは、
 * 並び順や絞り込みが変わると同じページ番号でも中身が入れ替わるため。
 */
export function productIndexQuery(
    filters: ProductIndexFilters,
    override: Partial<ProductIndexFilters> = {},
): ProductIndexQuery {
    const merged = { ...filters, ...override };

    return {
        ...(merged.category_id === null
            ? {}
            : { category_id: merged.category_id }),
        ...(merged.price_range === null
            ? {}
            : { price_range: merged.price_range }),
        ...(merged.stock === null ? {} : { stock: merged.stock }),
        ...(merged.sort === ProductSortValue.Newest
            ? {}
            : { sort: merged.sort }),
    };
}
