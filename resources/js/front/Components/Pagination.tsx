import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type PaginationProps = {
    links: { url: string | null; label: string; active: boolean }[];
};

export function Pagination({ links }: PaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav aria-label="ページ送り" className="mt-8">
            <ul className="flex justify-center gap-1">
                {links.map((link, index) => {
                    const className = cn(
                        'block rounded-full border border-line px-3.5 py-2 text-xs font-bold',
                        link.active
                            ? 'bg-brand text-white'
                            : 'bg-white text-ink',
                        !link.url && 'opacity-40',
                    );

                    return (
                        // ページ数が多いと省略記号（...）が前後2箇所に出るため、URLが無い項目は位置で識別する
                        <li key={link.url ?? `gap-${index}`}>
                            {link.url ? (
                                <Link
                                    href={link.url}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    className={className}
                                >
                                    {link.label}
                                </Link>
                            ) : (
                                <span className={className}>{link.label}</span>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
