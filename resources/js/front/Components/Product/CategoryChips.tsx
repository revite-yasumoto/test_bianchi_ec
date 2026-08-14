import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type CategoryChipsProps = {
    categories: { id: number; name: string }[];
    selectedId: number | null;
};

const CHIP_CLASS =
    'block rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors';

export function CategoryChips({ categories, selectedId }: CategoryChipsProps) {
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
                                href={route(
                                    'products.index',
                                    chip.id === null
                                        ? {}
                                        : { category_id: chip.id },
                                )}
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
