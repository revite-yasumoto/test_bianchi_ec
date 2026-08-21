import { Link } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';

type NewsDetail = {
    id: number;
    published_on: string;
    published_on_iso: string;
    category: string;
    category_tone: Tone;
    title: string;
    body: string;
};

type Props = {
    news: NewsDetail;
};

const DESCRIPTION_LENGTH = 120;

function toDescription(body: string): string {
    const text = body.replace(/\s+/g, ' ').trim();

    return text.length > DESCRIPTION_LENGTH
        ? `${text.slice(0, DESCRIPTION_LENGTH)}…`
        : text;
}

export default function Show({ news }: Props) {
    return (
        <FrontLayout title={news.title} description={toDescription(news.body)}>
            <div className="mx-auto max-w-[760px] px-[22px] py-[26px] pb-16">
                <Link
                    href={route('news.index')}
                    className="font-mono text-[11px] text-ink2"
                >
                    ← 新着ニュース一覧へ戻る
                </Link>

                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <time
                        dateTime={news.published_on_iso}
                        className="font-mono text-[11.5px] text-ink2"
                    >
                        {news.published_on}
                    </time>
                    <Badge tone={news.category_tone}>{news.category}</Badge>
                </div>

                <h1 className="mt-2 text-[26px] leading-[1.4] font-black">
                    {news.title}
                </h1>

                <p className="mt-5 border-t border-line pt-5 text-[13.5px] leading-[1.95] whitespace-pre-line text-ink2">
                    {news.body}
                </p>
            </div>
        </FrontLayout>
    );
}
