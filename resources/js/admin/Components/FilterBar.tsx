import type { ReactNode } from 'react';

type FilterBarProps = {
    resultCount: number;
    /** 渡すと「N件 / 全M件」の形式で絞り込み前の件数も表示する */
    totalCount?: number;
    onClear: () => void;
    children: ReactNode;
};

export function FilterBar({
    resultCount,
    totalCount,
    onClear,
    children,
}: FilterBarProps) {
    return (
        <div className="flex flex-wrap items-center gap-3 rounded-xl border border-admin-line bg-white p-4">
            <div className="flex flex-1 flex-wrap items-center gap-3">
                {children}
            </div>
            <div className="ml-auto flex items-center gap-3 text-sm text-admin-ink-muted">
                <span className="font-bold whitespace-nowrap">
                    {totalCount === undefined
                        ? `${resultCount}件`
                        : `${resultCount}件 / 全${totalCount}件`}
                </span>
                <button
                    type="button"
                    className="rounded-lg border border-admin-line px-3 py-1.5 font-bold text-admin-ink"
                    onClick={onClear}
                >
                    クリア
                </button>
            </div>
        </div>
    );
}
