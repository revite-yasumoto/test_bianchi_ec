import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type PaginationProps = {
    links: { url: string | null; label: string; active: boolean }[];
};

/** Laravel が返す `&laquo; Previous` 等のラベルを日本語に置き換える */
function formatLabel(label: string): string {
    if (label.includes('Previous')) {
        return '前へ';
    }

    if (label.includes('Next')) {
        return '次へ';
    }

    return label;
}

export function Pagination({ links }: PaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav aria-label="ページ送り" className="mt-4 flex justify-center gap-1">
            {links.map((link) => {
                const className = cn(
                    'rounded-lg border border-admin-line px-3 py-1.5 text-xs font-bold',
                    link.active
                        ? 'bg-admin-brand text-white'
                        : 'bg-white text-admin-ink',
                    !link.url && 'cursor-not-allowed opacity-40',
                );

                if (!link.url) {
                    return (
                        <span
                            key={link.label}
                            aria-disabled="true"
                            className={className}
                        >
                            {formatLabel(link.label)}
                        </span>
                    );
                }

                return (
                    <Link
                        key={link.label}
                        href={link.url}
                        aria-current={link.active ? 'page' : undefined}
                        preserveScroll
                        className={className}
                    >
                        {formatLabel(link.label)}
                    </Link>
                );
            })}
        </nav>
    );
}
