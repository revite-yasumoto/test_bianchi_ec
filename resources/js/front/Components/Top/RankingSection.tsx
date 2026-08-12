import { useId, useState } from 'react';
import { ProductCard } from '@/front/Components/Product/ProductCard';
import type { ProductCardData } from '@/front/Components/Product/ProductCard';
import { cn } from '@/lib/utils';

export type RankingTab = {
    key: string;
    label: string;
    category_id: number | null;
};

export type RankingItem = ProductCardData & { rank_position: number };

type RankingSectionProps = {
    tabs: RankingTab[];
    rankings: Record<string, RankingItem[]>;
    updatedAt: string | null;
};

export function RankingSection({
    tabs,
    rankings,
    updatedAt,
}: RankingSectionProps) {
    const [currentKey, setCurrentKey] = useState(tabs[0]?.key ?? '');
    const baseId = useId();

    if (tabs.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby={`${baseId}-heading`}
            className="px-5 pt-10 pb-2"
        >
            <div className="mb-4 flex flex-wrap items-baseline gap-3.5">
                <h2 id={`${baseId}-heading`} className="text-2xl font-black">
                    ランキング
                </h2>
                {updatedAt ? (
                    <p className="font-mono text-[10.5px] text-ink2">
                        UPDATED {updatedAt}
                    </p>
                ) : null}
            </div>

            <div
                role="tablist"
                aria-label="ランキングの絞り込み"
                className="mb-4 flex flex-wrap gap-1.5"
            >
                {tabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        role="tab"
                        id={`${baseId}-tab-${tab.key}`}
                        aria-selected={tab.key === currentKey}
                        aria-controls={`${baseId}-panel-${tab.key}`}
                        onClick={() => setCurrentKey(tab.key)}
                        className={cn(
                            'rounded-full border px-4 py-1.5 text-[12.5px] font-bold',
                            tab.key === currentKey
                                ? 'border-brand bg-brand text-white'
                                : 'border-line bg-white text-ink2',
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {tabs.map((tab) => (
                <div
                    key={tab.key}
                    role="tabpanel"
                    id={`${baseId}-panel-${tab.key}`}
                    aria-labelledby={`${baseId}-tab-${tab.key}`}
                    hidden={tab.key !== currentKey}
                >
                    <ul className="grid grid-cols-[repeat(auto-fill,minmax(190px,1fr))] gap-3.5">
                        {(rankings[tab.key] ?? []).map((item) => (
                            <li key={item.id}>
                                <ProductCard
                                    product={item}
                                    rank={item.rank_position}
                                />
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </section>
    );
}
