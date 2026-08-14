import type { ReactNode } from 'react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

type StaticPageLayoutProps = {
    title: string;
    description: string;
    children: ReactNode;
};

/** 買い物ガイド・法的3ページで共用する見出しと本文の枠 */
export function StaticPageLayout({
    title,
    description,
    children,
}: StaticPageLayoutProps) {
    return (
        <FrontLayout title={title} description={description}>
            <div className="mx-auto max-w-[760px] px-[22px] py-[26px] pb-16">
                <h1 className="text-[26px] font-black">{title}</h1>
                <div className="mt-6 flex flex-col gap-7">{children}</div>
            </div>
        </FrontLayout>
    );
}

type StaticSectionProps = {
    heading: string;
    children: ReactNode;
};

export function StaticSection({ heading, children }: StaticSectionProps) {
    return (
        <section>
            <h2 className="mb-2.5 text-[16px] font-extrabold">{heading}</h2>
            <div className="flex flex-col gap-2.5 text-[13px] leading-[1.95] text-ink2">
                {children}
            </div>
        </section>
    );
}
