import { Link } from '@inertiajs/react';
import { categoryTint } from '@/front/lib/tint';

export type CategoryEntryData = {
    id: number;
    name: string;
    product_count: number;
};

type CategoryEntriesProps = {
    entries: CategoryEntryData[];
};

export function CategoryEntries({ entries }: CategoryEntriesProps) {
    if (entries.length === 0) {
        return null;
    }

    return (
        <section className="px-5 pt-11 pb-2">
            <p className="mb-1.5 font-mono text-[11px] tracking-[.16em] text-brand">
                SHOP BY CATEGORY
            </p>
            <h2 className="mb-5 text-2xl font-black">購入方法から探す</h2>
            <ul className="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
                {entries.map((entry) => (
                    <li key={entry.id}>
                        <Link
                            href={route('products.index', {
                                category_id: entry.id,
                            })}
                            className="block overflow-hidden rounded-[18px] border border-line bg-white"
                        >
                            <div
                                className="h-22"
                                style={{
                                    backgroundImage: categoryTint(entry.name),
                                }}
                            />
                            <div className="px-4 py-3.5">
                                <p className="text-[14.5px] font-extrabold">
                                    {entry.name}
                                </p>
                                <p className="mt-0.5 text-[11.5px] text-ink2">
                                    {entry.product_count}アイテム
                                </p>
                            </div>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
