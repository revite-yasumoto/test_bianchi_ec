import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type SummaryCardProps = {
    label: string;
    value: string;
    note: string;
    tone?: 'ink' | 'brand' | 'danger';
    /** 指定するとカード全体が該当画面への導線になる */
    href?: string;
};

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white px-5 py-4';

const VALUE_TONE_CLASS = {
    ink: 'text-admin-ink',
    brand: 'text-admin-brand',
    danger: 'text-admin-danger',
} as const;

export function SummaryCard({
    label,
    value,
    note,
    tone = 'ink',
    href,
}: SummaryCardProps) {
    const content = (
        <>
            <p className="text-[11.5px] font-bold text-admin-ink-muted">
                {label}
            </p>
            <p
                className={cn(
                    'mt-2 font-mono text-2xl font-bold',
                    VALUE_TONE_CLASS[tone],
                )}
            >
                {value}
            </p>
            <p className="mt-1.5 text-[11px] text-admin-ink-muted">{note}</p>
        </>
    );

    if (href === undefined) {
        return <div className={CARD_CLASS}>{content}</div>;
    }

    return (
        <Link href={href} className={cn(CARD_CLASS, 'block')}>
            {content}
        </Link>
    );
}
