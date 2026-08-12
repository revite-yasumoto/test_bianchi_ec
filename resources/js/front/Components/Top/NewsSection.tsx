import { Link } from '@inertiajs/react';
import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';

export type NewsRow = {
    id: number;
    published_on: string;
    published_on_iso: string;
    category: string;
    category_tone: Tone;
    title: string;
};

type NewsSectionProps = {
    news: NewsRow[];
};

export function NewsSection({ news }: NewsSectionProps) {
    if (news.length === 0) {
        return null;
    }

    // 新着ニュース一覧は単位18で実装するため、それまでは行をリンクにしない
    const hasNewsIndex = route().has('news.index');

    return (
        <section className="px-5 pt-10 pb-14">
            <div className="mb-3.5 flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="text-2xl font-black">新着ニュース</h2>
                {hasNewsIndex ? (
                    <Link
                        href={route('news.index')}
                        className="text-[12.5px] font-bold text-brand"
                    >
                        すべて見る →
                    </Link>
                ) : null}
            </div>

            <ul className="border-t border-line">
                {news.map((row) => {
                    const content = (
                        <>
                            <time
                                dateTime={row.published_on_iso}
                                className="shrink-0 font-mono text-[11.5px] text-ink2"
                            >
                                {row.published_on}
                            </time>
                            <Badge
                                tone={row.category_tone}
                                className="shrink-0"
                            >
                                {row.category}
                            </Badge>
                            <span className="min-w-0 text-[13.5px] font-semibold break-words">
                                {row.title}
                            </span>
                        </>
                    );

                    return (
                        <li key={row.id} className="border-b border-line">
                            {hasNewsIndex ? (
                                <Link
                                    href={route('news.index')}
                                    className="flex flex-wrap items-center gap-3.5 py-3.5"
                                >
                                    {content}
                                </Link>
                            ) : (
                                <div className="flex flex-wrap items-center gap-3.5 py-3.5">
                                    {content}
                                </div>
                            )}
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
