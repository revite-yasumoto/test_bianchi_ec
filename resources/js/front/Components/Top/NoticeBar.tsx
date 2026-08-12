import { Link } from '@inertiajs/react';

type NoticeBarProps = {
    notice: { id: number; title: string };
};

const BAR_CLASS =
    'flex w-full items-center gap-3 border-b border-[#F0DFBE] bg-[#FDF3E3] px-5 py-2.5 text-left';

/** TOP最上部の1行。重要なお知らせが掲載中のときだけ表示する */
export function NoticeBar({ notice }: NoticeBarProps) {
    const content = (
        <>
            <span className="shrink-0 rounded bg-coral px-2 py-0.5 font-mono text-[10px] tracking-[.1em] text-white">
                重要
            </span>
            <span className="text-[13px] font-semibold text-[#7A5A1E]">
                {notice.title}
            </span>
            <span className="ml-auto shrink-0 text-xs text-[#9A7C3E]">
                詳細 →
            </span>
        </>
    );

    // 重要なお知らせ一覧は単位18で実装するため、それまではリンクにしない
    if (!route().has('notices.index')) {
        return <div className={BAR_CLASS}>{content}</div>;
    }

    return (
        <Link href={route('notices.index')} className={BAR_CLASS}>
            {content}
        </Link>
    );
}
