import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type MyPageTab = {
    key: string;
    label: string;
    routeName: string;
    /** 子ページ（注文詳細等）を開いている間もこのタブを現在地として扱うためのパターン */
    childPattern?: string;
};

const TABS: MyPageTab[] = [
    {
        key: 'orders',
        label: '注文履歴',
        routeName: 'mypage.index',
        childPattern: 'mypage.orders.*',
    },
    { key: 'favorites', label: 'お気に入り', routeName: 'mypage.favorites' },
    { key: 'addresses', label: '配送先住所', routeName: 'mypage.addresses' },
    { key: 'profile', label: '会員情報変更', routeName: 'mypage.profile' },
    { key: 'password', label: 'パスワード変更', routeName: 'mypage.password' },
];

function isCurrent(tab: MyPageTab): boolean {
    return (
        route().current(tab.routeName) ||
        (tab.childPattern !== undefined && route().current(tab.childPattern))
    );
}

export function TabNav() {
    return (
        <nav aria-label="マイページメニュー">
            <ul className="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible">
                {TABS.map((tab) => {
                    const current = isCurrent(tab);

                    return (
                        <li key={tab.key} className="shrink-0 lg:shrink">
                            <Link
                                href={route(tab.routeName)}
                                aria-current={current ? 'page' : undefined}
                                className={cn(
                                    'block rounded-full px-4 py-2.5 text-[13px] font-bold whitespace-nowrap lg:rounded-xl',
                                    current
                                        ? 'bg-brand text-white'
                                        : 'bg-bg2 text-ink2',
                                )}
                            >
                                {tab.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
