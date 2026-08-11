import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { Tone } from '@/shared/lib/tone';

type BadgeProps = {
    tone: Tone;
    className?: string;
    children: ReactNode;
};

/** ステータス表示のピル。配色は Tailwind のトークン外のため tone の実値をstyleで当てる */
export function Badge({ tone, className, children }: BadgeProps) {
    return (
        <span
            style={{ color: tone.fg, backgroundColor: tone.bg }}
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold whitespace-nowrap',
                className,
            )}
        >
            {children}
        </span>
    );
}
