import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PriceRange } from '@/shared/lib/enums';

type CategoryChipsProps = {
    categories: { id: number; name: string }[];
    selectedId: number | null;
    /** 価格帯の絞り込みを保ったまま遷移するため、現在の選択を引き継ぐ */
    priceRange: PriceRange | null;
};

const CHIP_CLASS =
    'block rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors';

export function CategoryChips({
    categories,
    selectedId,
    priceRange,
}: CategoryChipsProps) {
    const chips = [
        { id: null, name: 'すべて' },
        ...categories.map((category) => ({
            id: category.id as number | null,
            name: category.name,
        })),
    ];

    return (
        <nav aria-label="カテゴリ絞り込み">
            <ul className="flex flex-wrap gap-1.5">
                {chips.map((chip) => {
                    const selected = chip.id === selectedId;

                    return (
                        <li key={chip.id ?? 'all'}>
                            <Link
                                href={route('products.index', {
                                    ...(chip.id === null
                                        ? {}
                                        : { category_id: chip.id }),
                                    ...(priceRange === null
                                        ? {}
                                        : { price_range: priceRange }),
                                })}
                                aria-current={selected ? 'page' : undefined}
                                className={cn(
                                    CHIP_CLASS,
                                    selected
                                        ? 'border-brand bg-brand text-white'
                                        : 'border-line bg-white text-ink2',
                                )}
                            >
                                {chip.name}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
