import { router, usePage } from '@inertiajs/react';
import { NAV_MENU } from './NavMenu';
import { NavLink } from './NavLink';

type MobileMenuProps = {
    isOpen: boolean;
    onNavigate: () => void;
};

export function MobileMenu({ isOpen, onNavigate }: MobileMenuProps) {
    const { auth } = usePage<FrontSharedProps>().props;

    if (!isOpen) {
        return null;
    }

    return (
        <div className="border-t border-line bg-white px-5 py-3 lg:hidden">
            {auth.user ? (
                <p className="truncate border-b border-line pb-3 text-base font-bold text-ink2">
                    {auth.user.name} 様
                </p>
            ) : null}

            <nav
                aria-label="メインメニュー（モバイル）"
                className="flex flex-col"
                onClick={onNavigate}
            >
                {NAV_MENU.map((item) => (
                    <NavLink
                        key={item.key}
                        item={item}
                        className="border-b border-line py-3 text-base font-bold"
                        currentClassName="text-brand"
                    />
                ))}
                {auth.user ? (
                    <button
                        type="button"
                        onClick={() => router.post(route('logout'))}
                        className="py-3 text-left text-base font-bold"
                    >
                        ログアウト
                    </button>
                ) : null}
            </nav>
        </div>
    );
}
