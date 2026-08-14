import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PriceRange } from '@/shared/lib/enums';

type PriceRangeChipsProps = {
    ranges: { value: PriceRange; label: string }[];
    selectedValue: PriceRange | null;
    /** カテゴリの絞り込みを保ったまま遷移するため、現在の選択を引き継ぐ */
    categoryId: number | null;
};

const CHIP_CLASS =
    'block rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors';

export function PriceRangeChips({
    ranges,
    selectedValue,
    categoryId,
}: PriceRangeChipsProps) {
    const chips = [
        { value: null as PriceRange | null, label: 'すべて' },
        ...ranges.map((range) => ({
            value: range.value as PriceRange | null,
            label: range.label,
        })),
    ];

    return (
        <nav aria-label="価格帯絞り込み">
            <ul className="flex flex-wrap gap-1.5">
                {chips.map((chip) => {
                    const selected = chip.value === selectedValue;

                    return (
                        <li key={chip.value ?? 'all'}>
                            <Link
                                href={route('products.index', {
                                    ...(categoryId === null
                                        ? {}
                                        : { category_id: categoryId }),
                                    ...(chip.value === null
                                        ? {}
                                        : { price_range: chip.value }),
                                })}
                                aria-current={selected ? 'page' : undefined}
                                className={cn(
                                    CHIP_CLASS,
                                    selected
                                        ? 'border-brand bg-brand text-white'
                                        : 'border-line bg-white text-ink2',
                                )}
                            >
                                {chip.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
