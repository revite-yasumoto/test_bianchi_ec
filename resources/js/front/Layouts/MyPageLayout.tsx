import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { TabNav } from '@/front/Components/MyPage/TabNav';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

type MyPageLayoutProps = {
    title: string;
    description: string;
    /** ページの主題を表す見出し。タブ名、注文詳細なら注文番号 */
    heading: string;
    children: ReactNode;
};

export function MyPageLayout({
    title,
    description,
    heading,
    children,
}: MyPageLayoutProps) {
    const { auth } = usePage<FrontSharedProps>().props;

    return (
        <FrontLayout title={title} description={description}>
            <div className="px-[22px] py-[26px] pb-14">
                <p className="font-mono text-[11px] tracking-[.14em] text-ink2">
                    MY PAGE
                </p>
                <h1 className="mt-1 text-[26px] font-black">{heading}</h1>
                {auth.user ? (
                    <p className="mt-1.5 text-[12.5px] text-ink2">
                        {auth.user.name} 様
                        <span className="ml-2.5 font-mono">
                            {auth.user.member_code}
                        </span>
                    </p>
                ) : null}

                <div className="mt-6 grid items-start gap-6 lg:grid-cols-[200px_minmax(0,1fr)]">
                    <TabNav />
                    <div className="min-w-0">{children}</div>
                </div>
            </div>
        </FrontLayout>
    );
}
