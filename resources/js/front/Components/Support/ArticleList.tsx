import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export type Article = {
    id: number;
    title: string;
    /** 行全体のリンク先（記事詳細） */
    href: string;
    /** タイトルの左に出す補助情報（掲載日・掲載期間など） */
    meta: ReactNode;
};

type ArticleListProps = {
    articles: Article[];
};

/** 新着ニュース・重要なお知らせで共用する、行全体が詳細ページへのリンクになる一覧 */
export function ArticleList({ articles }: ArticleListProps) {
    return (
        <ul className="border-t border-line">
            {articles.map((article) => (
                <li key={article.id} className="border-b border-line">
                    <Link
                        href={article.href}
                        className="flex flex-wrap items-center gap-3.5 py-3.5"
                    >
                        {article.meta}
                        <span className="min-w-0 text-[13.5px] font-semibold break-words">
                            {article.title}
                        </span>
                        <span
                            aria-hidden="true"
                            className="ml-auto shrink-0 text-xs text-ink2"
                        >
                            →
                        </span>
                    </Link>
                </li>
            ))}
        </ul>
    );
}
