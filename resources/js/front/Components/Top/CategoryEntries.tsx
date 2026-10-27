import { Link } from '@inertiajs/react';
import { CategorySilhouette } from '@/front/Components/Product/CategorySilhouette';
import { categoryTint } from '@/front/lib/tint';

export type CategoryEntryData = {
    id: number;
    name: string;
    product_count: number;
    image_url: string | null;
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
            <p className="mb-1.5 font-mono text-sm tracking-[.16em] text-brand">
                SHOP BY CATEGORY
            </p>
            <h2 className="mb-5 text-2xl font-black">カテゴリー</h2>
            <ul className="grid grid-cols-[repeat(auto-fill,minmax(190px,1fr))] gap-3.5">
                {entries.map((entry) => (
                    <li key={entry.id}>
                        <Link
                            href={route('products.index', {
                                category_id: entry.id,
                            })}
                            className="block overflow-hidden rounded-[18px] border border-line bg-white"
                        >
                            {entry.image_url ? (
                                <img
                                    src={entry.image_url}
                                    alt=""
                                    width={400}
                                    height={300}
                                    loading="lazy"
                                    className="aspect-4/3 w-full object-cover"
                                />
                            ) : (
                                <div
                                    className="flex aspect-4/3 items-center justify-center"
                                    style={{
                                        backgroundImage: categoryTint(
                                            entry.name,
                                        ),
                                    }}
                                >
                                    <CategorySilhouette
                                        categoryName={entry.name}
                                        className="w-1/2 text-white/35"
                                    />
                                </div>
                            )}
                            <div className="px-4 pt-3.5 pb-4">
                                <p className="text-base font-extrabold">
                                    {entry.name}
                                </p>
                                <p className="mt-0.5 text-sm text-ink2">
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
