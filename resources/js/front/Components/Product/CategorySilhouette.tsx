import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * 商品画像が未登録のときに背景へ重ねる商材のシルエット。
 * キーは `resources/js/front/lib/tint.ts` の配色定義と揃える。
 */
const SILHOUETTES: Record<string, ReactNode> = {
    ロードバイク: (
        <>
            <circle cx="26" cy="50" r="15" />
            <circle cx="94" cy="50" r="15" />
            <path d="M26 50 46 22h27l21 28M46 22l14 28M73 22l4-8M40 16h13m24 0h9" />
        </>
    ),
    MTB: (
        <>
            <circle cx="26" cy="49" r="17" />
            <circle cx="94" cy="49" r="17" />
            <path d="M26 49 47 24h26l21 25M47 24l13 25M73 24v-8m-13 0h26M47 24 40 12" />
        </>
    ),
    シティ: (
        <>
            <circle cx="26" cy="52" r="15" />
            <circle cx="94" cy="52" r="15" />
            <path d="M26 52 48 26h24l22 26M48 26l12 26M72 26v-8m-8 0h16" />
            <path d="M78 18h16v12H78z" />
            <path d="M12 42a15 15 0 0 1 28 0" />
        </>
    ),
    eバイク: (
        <>
            <circle cx="26" cy="50" r="16" />
            <circle cx="94" cy="50" r="16" />
            <path d="M26 50 47 24h26l21 26M47 24l13 26M73 24v-8m-9 0h18" />
            <rect x="42" y="32" width="26" height="11" rx="3" />
            <path d="M52 35l-3 5h6l-3 5" />
        </>
    ),
    パーツ: (
        <>
            <rect x="40" y="10" width="26" height="52" rx="9" />
            <path d="M46 10v-4h14v4" />
            <path d="M74 16v40a8 8 0 0 1-8 8m8-48h8m-8 48h8" />
        </>
    ),
    アパレル: (
        <>
            <path d="M44 14h32l20 12-9 13-8-5v30H41V34l-8 5-9-13z" />
            <path d="M50 14a10 8 0 0 0 20 0" />
        </>
    ),
};

/** カテゴリの登録がないときに使う汎用の自転車 */
const FALLBACK: ReactNode = (
    <>
        <circle cx="26" cy="50" r="15" />
        <circle cx="94" cy="50" r="15" />
        <path d="M26 50 47 24h26l21 26M47 24l13 26M73 24v-8m-9 0h18" />
    </>
);

type CategorySilhouetteProps = {
    categoryName: string;
    className?: string;
};

export function CategorySilhouette({
    categoryName,
    className,
}: CategorySilhouetteProps) {
    return (
        <svg
            viewBox="0 0 120 72"
            fill="none"
            stroke="currentColor"
            strokeWidth={3}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className={cn('h-full w-full', className)}
        >
            {SILHOUETTES[categoryName] ?? FALLBACK}
        </svg>
    );
}
