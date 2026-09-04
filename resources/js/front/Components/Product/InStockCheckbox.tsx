import { router } from '@inertiajs/react';
import {
    productIndexQuery,
    type ProductIndexFilters,
} from '@/front/lib/productIndexQuery';
import { Checkbox } from '@/shared/Components/Checkbox';

type InStockCheckboxProps = {
    filters: ProductIndexFilters;
};

export function InStockCheckbox({ filters }: InStockCheckboxProps) {
    return (
        <Checkbox
            id="product-in-stock"
            checked={filters.in_stock}
            onChange={(event) =>
                router.get(
                    route('products.index'),
                    productIndexQuery(filters, {
                        in_stock: event.target.checked,
                    }),
                    // ページを作り直すとチェックボックスからフォーカスが外れ、
                    // キーボードでの続けての切り替えができなくなる
                    { preserveState: true, preserveScroll: true },
                )
            }
        >
            在庫ありのみ表示
        </Checkbox>
    );
}
