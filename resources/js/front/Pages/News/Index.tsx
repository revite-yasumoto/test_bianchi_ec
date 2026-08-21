import { ArticleList } from '@/front/Components/Support/ArticleList';
import { Pagination } from '@/front/Components/Pagination';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Badge } from '@/shared/Components/Badge';
import { EmptyState } from '@/shared/Components/EmptyState';
import type { Tone } from '@/shared/lib/tone';

type NewsArticle = {
    id: number;
    published_on: string;
    published_on_iso: string;
    category: string;
    category_tone: Tone;
    title: string;
};

type Props = {
    news: Paginated<NewsArticle>;
};

export default function Index({ news }: Props) {
    return (
        <FrontLayout
            title="新着ニュース"
            description="新商品・商品情報・お知らせなど、Bianchiストアからの最新のお知らせです。"
        >
            <div className="mx-auto max-w-[760px] px-[22px] py-[26px] pb-16">
                <h1 className="mb-5 text-[26px] font-black">新着ニュース</h1>

                {news.data.length === 0 ? (
                    <EmptyState message="お知らせはまだありません。" />
                ) : (
                    <>
                        <ArticleList
                            articles={news.data.map((article) => ({
                                id: article.id,
                                title: article.title,
                                href: route('news.show', [article.id]),
                                meta: (
                                    <>
                                        <time
                                            dateTime={article.published_on_iso}
                                            className="shrink-0 font-mono text-[11.5px] text-ink2"
                                        >
                                            {article.published_on}
                                        </time>
                                        <Badge
                                            tone={article.category_tone}
                                            className="shrink-0"
                                        >
                                            {article.category}
                                        </Badge>
                                    </>
                                ),
                            }))}
                        />
                        <Pagination links={news.links} />
                    </>
                )}
            </div>
        </FrontLayout>
    );
}
