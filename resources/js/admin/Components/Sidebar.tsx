import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { SIDEBAR_MENU, type SidebarMenuItem } from './SidebarMenu';

function isItemEnabled(item: SidebarMenuItem): boolean {
    const { routeName } = item;

    return routeName !== undefined && route().has(routeName);
}

function isItemCurrent(item: SidebarMenuItem): boolean {
    const { routeName } = item;

    return (
        routeName !== undefined &&
        route().has(routeName) &&
        route().current(routeName)
    );
}

function MenuLink({
    item,
    indented = false,
}: {
    item: SidebarMenuItem;
    indented?: boolean;
}) {
    const { routeName } = item;
    const enabled = isItemEnabled(item);
    const current = isItemCurrent(item);

    const className = cn(
        'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-base font-bold',
        indented && 'pl-8 font-medium',
        current ? 'bg-admin-brand text-white' : 'text-admin-ink',
        !enabled && 'cursor-not-allowed text-admin-ink-muted opacity-60',
    );

    const dot = !indented ? (
        <span
            className={cn(
                'h-1.5 w-1.5 rounded-full',
                current ? 'bg-white' : 'bg-transparent',
            )}
            aria-hidden="true"
        />
    ) : null;

    if (!enabled || routeName === undefined) {
        return (
            <button type="button" className={className} disabled>
                {dot}
                {item.label}
            </button>
        );
    }

    return (
        <Link
            href={route(routeName)}
            className={className}
            aria-current={current ? 'page' : undefined}
        >
            {dot}
            {item.label}
        </Link>
    );
}

function MenuGroup({ item }: { item: SidebarMenuItem }) {
    const [isOpen, setIsOpen] = useState(() =>
        (item.children ?? []).some((child) => isItemCurrent(child)),
    );

    return (
        <div>
            <button
                type="button"
                className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-base font-bold text-admin-ink"
                aria-expanded={isOpen}
                onClick={() => setIsOpen((previous) => !previous)}
            >
                <span aria-hidden="true">{isOpen ? '▾' : '▸'}</span>
                {item.label}
            </button>
            {isOpen ? (
                <ul className="flex flex-col gap-0.5 pb-1 pl-3">
                    {(item.children ?? []).map((child) => (
                        <li key={child.key}>
                            <MenuLink item={child} indented />
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

export function Sidebar() {
    const { auth } = usePage<AdminSharedProps>().props;

    return (
        <aside className="flex w-admin-sidebar flex-shrink-0 flex-col border-r border-admin-line bg-admin-sidebar-bg">
            <div className="border-b border-admin-line px-4 py-4">
                <p className="text-base font-extrabold tracking-wide text-admin-brand-deep">
                    Bianchi
                </p>
                <p className="text-sm tracking-widest text-admin-ink-muted">
                    ADMIN CONSOLE
                </p>
            </div>
            <nav className="flex flex-1 flex-col overflow-y-auto p-2">
                <ul className="flex flex-col gap-0.5">
                    {SIDEBAR_MENU.map((item) => (
                        <li key={item.key}>
                            {item.children ? (
                                <MenuGroup item={item} />
                            ) : (
                                <MenuLink item={item} />
                            )}
                        </li>
                    ))}
                </ul>
            </nav>
            <div className="border-t border-admin-line px-3 py-3">
                <p className="truncate text-sm font-bold text-admin-ink">
                    {auth.admin?.name}
                </p>
                <p className="truncate text-sm text-admin-ink-muted">
                    {auth.admin?.email}
                </p>
            </div>
        </aside>
    );
}
