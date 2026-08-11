import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { NavMenuItem } from './NavMenu';

type NavLinkProps = {
    item: NavMenuItem;
    className?: string;
    currentClassName?: string;
};

/**
 * 対応するルートが未実装（`route().has()` が false）の間は非活性表示にする。
 * 後続の単位がルートを実装した時点で、本ファイルの変更なしにリンクとして有効化される。
 */
export function NavLink({ item, className, currentClassName }: NavLinkProps) {
    const enabled = route().has(item.routeName);
    const current = enabled && route().current(item.routeName);

    if (!enabled) {
        return (
            <span
                aria-disabled="true"
                className={cn(className, 'cursor-not-allowed opacity-40')}
            >
                {item.label}
            </span>
        );
    }

    return (
        <Link
            href={route(item.routeName)}
            aria-current={current ? 'page' : undefined}
            className={cn(className, current && currentClassName)}
        >
            {item.label}
        </Link>
    );
}
