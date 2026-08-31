import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type FilterChipsProps<T extends number | string> = {
    /** 軸の名前。チップ列の先頭に表示し、絞り込みの読み上げ名にも使う */
    label: string;
    options: { value: T; label: string }[];
    selected: T | null;
    /** 「すべて」は `null` で渡る */
    buildHref: (value: T | null) => string;
};

const CHIP_CLASS =
    'block rounded-full border px-4 py-2 text-[15px] font-bold transition-colors';

export function FilterChips<T extends number | string>({
    label,
    options,
    selected,
    buildHref,
}: FilterChipsProps<T>) {
    const chips: { value: T | null; label: string }[] = [
        { value: null, label: 'すべて' },
        ...options,
    ];

    return (
        <nav
            aria-label={`${label}絞り込み`}
            className="flex flex-wrap items-center gap-x-2.5 gap-y-1.5"
        >
            <span className="text-[13px] font-bold text-ink2">{label}</span>
            <ul className="flex flex-wrap gap-1.5">
                {chips.map((chip) => {
                    const isSelected = chip.value === selected;

                    return (
                        <li key={chip.value ?? 'all'}>
                            <Link
                                href={buildHref(chip.value)}
                                aria-current={isSelected ? 'page' : undefined}
                                className={cn(
                                    CHIP_CLASS,
                                    isSelected
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
