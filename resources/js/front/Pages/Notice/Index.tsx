import { ArticleList } from '@/front/Components/Support/ArticleList';
import { Pagination } from '@/front/Components/Pagination';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { EmptyState } from '@/shared/Components/EmptyState';

type NoticeArticle = {
    id: number;
    title: string;
    period_start: string;
    period_start_iso: string;
    period_end: string;
    period_end_iso: string;
};

type Props = {
    notices: Paginated<NoticeArticle>;
};

export default function Index({ notices }: Props) {
    return (
        <FrontLayout
            title="重要なお知らせ"
            description="サービスの停止予定や配送の遅延など、掲載中の重要なお知らせです。"
        >
            <div className="mx-auto max-w-[760px] px-[22px] py-[26px] pb-16">
                <h1 className="mb-5 text-[26px] font-black">重要なお知らせ</h1>

                {notices.data.length === 0 ? (
                    <EmptyState message="掲載中のお知らせはありません。" />
                ) : (
                    <>
                        <ArticleList
                            articles={notices.data.map((notice) => ({
                                id: notice.id,
                                title: notice.title,
                                href: route('notices.show', [notice.id]),
                                meta: (
                                    <>
                                        <span className="shrink-0 rounded bg-coral px-2 py-0.5 font-mono text-[10px] tracking-[.1em] text-white">
                                            重要
                                        </span>
                                        <span className="shrink-0 font-mono text-[11.5px] text-ink2">
                                            <time
                                                dateTime={
                                                    notice.period_start_iso
                                                }
                                            >
                                                {notice.period_start}
                                            </time>
                                            {' - '}
                                            <time
                                                dateTime={notice.period_end_iso}
                                            >
                                                {notice.period_end}
                                            </time>
                                        </span>
                                    </>
                                ),
                            }))}
                        />
                        <Pagination links={notices.links} />
                    </>
                )}
            </div>
        </FrontLayout>
    );
}
