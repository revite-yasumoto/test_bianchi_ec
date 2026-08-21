import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { ContactTab } from '@/shared/lib/enums';

type TabNavProps = {
    current: ContactTab;
    /** タブを切り替えても引き継ぐ絞り込み条件 */
    query: Record<string, string>;
};

const TABS: { value: ContactTab; label: string }[] = [
    { value: ContactTab.General, label: '通常のお問い合わせ' },
    { value: ContactTab.Product, label: '商品からのお問い合わせ' },
];

export function TabNav({ current, query }: TabNavProps) {
    return (
        <nav aria-label="お問い合わせの種別">
            <ul className="flex flex-wrap items-center gap-1 border-b border-admin-line">
                {TABS.map((tab) => {
                    const isCurrent = tab.value === current;

                    return (
                        <li key={tab.value}>
                            <Link
                                href={route('admin.contacts.index', {
                                    ...query,
                                    tab: tab.value,
                                })}
                                aria-current={isCurrent ? 'page' : undefined}
                                className={cn(
                                    'block border-b-2 px-5 py-3 text-[13px] font-bold',
                                    isCurrent
                                        ? 'border-admin-brand text-admin-ink'
                                        : 'border-transparent text-admin-ink-muted',
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
