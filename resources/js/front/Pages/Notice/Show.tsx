import { Link } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

type NoticeDetail = {
    id: number;
    title: string;
    body: string;
    period_start: string;
    period_start_iso: string;
    period_end: string;
    period_end_iso: string;
};

type Props = {
    notice: NoticeDetail;
};

const DESCRIPTION_LENGTH = 120;

function toDescription(body: string): string {
    const text = body.replace(/\s+/g, ' ').trim();

    return text.length > DESCRIPTION_LENGTH
        ? `${text.slice(0, DESCRIPTION_LENGTH)}…`
        : text;
}

export default function Show({ notice }: Props) {
    return (
        <FrontLayout
            title={notice.title}
            description={toDescription(notice.body)}
        >
            <div className="mx-auto max-w-[760px] px-[22px] py-[26px] pb-16">
                <Link
                    href={route('notices.index')}
                    className="font-mono text-[11px] text-ink2"
                >
                    ← 重要なお知らせ一覧へ戻る
                </Link>

                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <span className="rounded bg-coral px-2 py-0.5 font-mono text-[10px] tracking-[.1em] text-white">
                        重要
                    </span>
                    <span className="font-mono text-[11.5px] text-ink2">
                        <time dateTime={notice.period_start_iso}>
                            {notice.period_start}
                        </time>
                        {' - '}
                        <time dateTime={notice.period_end_iso}>
                            {notice.period_end}
                        </time>
                    </span>
                </div>

                <h1 className="mt-2 text-[26px] leading-[1.4] font-black">
                    {notice.title}
                </h1>

                <p className="mt-5 border-t border-line pt-5 text-[13.5px] leading-[1.95] whitespace-pre-line text-ink2">
                    {notice.body}
                </p>
            </div>
        </FrontLayout>
    );
}
