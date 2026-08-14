import { Link } from '@inertiajs/react';

type NoticeBarProps = {
    notice: { id: number; title: string };
};

/** TOP最上部の1行。重要なお知らせが掲載中のときだけ表示する */
export function NoticeBar({ notice }: NoticeBarProps) {
    return (
        <Link
            href={route('notices.index')}
            className="flex w-full items-center gap-3 border-b border-notice-line bg-notice-bg px-5 py-2.5 text-left"
        >
            <span className="shrink-0 rounded bg-coral px-2 py-0.5 font-mono text-[10px] tracking-[.1em] text-white">
                重要
            </span>
            <span className="text-[13px] font-semibold text-notice-ink">
                {notice.title}
            </span>
            <span className="ml-auto shrink-0 text-xs text-notice-ink-muted">
                詳細 →
            </span>
        </Link>
    );
}
