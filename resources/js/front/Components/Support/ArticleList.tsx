import type { ReactNode } from 'react';

export type Article = {
    id: number;
    title: string;
    body: string;
    /** タイトルの左に出す補助情報（掲載日・掲載期間など） */
    meta: ReactNode;
};

type ArticleListProps = {
    articles: Article[];
};

/**
 * 新着ニュース・重要なお知らせで共用する開閉リスト。
 * 開閉状態は details 要素が持つため、状態管理と aria 属性を自前で持たない。
 */
export function ArticleList({ articles }: ArticleListProps) {
    return (
        <ul className="border-t border-line">
            {articles.map((article) => (
                <li key={article.id} className="border-b border-line">
                    <details className="group">
                        <summary className="flex cursor-pointer flex-wrap items-center gap-3.5 py-3.5 marker:content-none [&::-webkit-details-marker]:hidden">
                            {article.meta}
                            <span className="min-w-0 text-[13.5px] font-semibold break-words">
                                {article.title}
                            </span>
                            <span
                                aria-hidden="true"
                                className="ml-auto shrink-0 text-xs text-ink2 group-open:hidden"
                            >
                                ＋
                            </span>
                            <span
                                aria-hidden="true"
                                className="ml-auto hidden shrink-0 text-xs text-ink2 group-open:block"
                            >
                                −
                            </span>
                        </summary>
                        <p className="pb-4 text-[12.5px] leading-[1.95] whitespace-pre-line text-ink2">
                            {article.body}
                        </p>
                    </details>
                </li>
            ))}
        </ul>
    );
}
