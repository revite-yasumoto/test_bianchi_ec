import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { MobileMenu } from './MobileMenu';
import { NAV_MENU } from './NavMenu';
import { NavLink } from './NavLink';

type HeaderProps = {
    onOpenCart: () => void;
};

export function Header({ onOpenCart }: HeaderProps) {
    const { auth, cartCount, favoriteCount } =
        usePage<FrontSharedProps>().props;
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    return (
        <header className="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
            <div className="flex items-center gap-4 px-5 py-3">
                <Link
                    href={route('top')}
                    className="flex shrink-0 flex-col leading-none"
                >
                    <span className="font-sans text-xl font-extrabold tracking-[.14em] text-brand-deep">
                        Bianchi
                    </span>
                    <span className="mt-1 font-mono text-[8.5px] tracking-[.24em] text-ink2">
                        BICYCLE STORE
                    </span>
                </Link>

                <nav
                    aria-label="メインメニュー"
                    className="ml-2 hidden min-w-0 flex-1 gap-4 lg:flex"
                >
                    {NAV_MENU.map((item) => (
                        <NavLink
                            key={item.key}
                            item={item}
                            className="text-[13.5px] font-medium whitespace-nowrap text-ink2"
                            currentClassName="font-bold text-ink"
                        />
                    ))}
                </nav>

                <div className="ml-auto flex shrink-0 items-center gap-2">
                    {auth.user ? (
                        <>
                            <NavLink
                                item={{
                                    key: 'favorites',
                                    label: `お気に入り ${favoriteCount}`,
                                    routeName: 'mypage.favorites',
                                }}
                                className="rounded-full border border-line px-3 py-2 text-xs font-bold whitespace-nowrap"
                            />
                            <button
                                type="button"
                                onClick={onOpenCart}
                                className="rounded-full bg-coral px-3.5 py-2 text-xs font-bold whitespace-nowrap text-white"
                            >
                                カート
                                <span className="ml-1.5 font-mono">
                                    {cartCount}
                                </span>
                            </button>
                            <button
                                type="button"
                                onClick={() => router.post(route('logout'))}
                                className="hidden text-xs font-bold text-ink2 lg:block"
                            >
                                ログアウト
                            </button>
                        </>
                    ) : (
                        <>
                            <Link
                                href={route('login')}
                                className="rounded-full border border-line px-3.5 py-2 text-xs font-bold whitespace-nowrap"
                            >
                                ログイン
                            </Link>
                            <Link
                                href={route('register')}
                                className="rounded-full bg-coral px-3.5 py-2 text-xs font-bold whitespace-nowrap text-white"
                            >
                                会員登録
                            </Link>
                        </>
                    )}

                    <button
                        type="button"
                        aria-label="メニューを開く"
                        aria-expanded={isMenuOpen}
                        onClick={() => setIsMenuOpen((previous) => !previous)}
                        className="flex h-9 w-9 items-center justify-center rounded-full border border-line text-base lg:hidden"
                    >
                        ≡
                    </button>
                </div>
            </div>

            <MobileMenu
                isOpen={isMenuOpen}
                onNavigate={() => setIsMenuOpen(false)}
            />
        </header>
    );
}
