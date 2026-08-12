import { cn } from '@/lib/utils';
import { yen } from '@/shared/lib/yen';

export type SalesChartBar = {
    label: string;
    amount: number;
};

type SalesBarChartProps = {
    bars: SalesChartBar[];
};

/** 棒の高さは日ごとに変わる連続値のため、Tailwind のクラスではなく style で指定する */
function heightRatio(amount: number, max: number): string {
    if (max === 0) {
        return '0%';
    }

    return `${Math.round((amount / max) * 100)}%`;
}

/** 棒の上に添える値。桁が多い日でも列幅に収まるよう1万円以上は「万」表記にする */
function formatAmount(amount: number): string {
    if (amount >= 10000) {
        return `${(amount / 10000).toFixed(1)}万`;
    }

    return amount.toLocaleString('ja-JP');
}

export function SalesBarChart({ bars }: SalesBarChartProps) {
    const max = Math.max(...bars.map((bar) => bar.amount), 0);

    return (
        <ul className="flex h-[170px] items-end gap-2.5">
            {bars.map((bar, index) => (
                <li
                    key={bar.label}
                    className="flex h-full flex-1 flex-col items-center gap-1.5"
                >
                    <div className="flex min-h-0 w-full flex-1 flex-col items-center justify-end gap-1.5">
                        <span className="flex-shrink-0 font-mono text-[9.5px] text-admin-ink-muted">
                            {formatAmount(bar.amount)}
                        </span>
                        <div
                            style={{ height: heightRatio(bar.amount, max) }}
                            className={cn(
                                'w-full flex-shrink-0 rounded-t-md',
                                index === bars.length - 1
                                    ? 'bg-admin-brand'
                                    : 'bg-admin-brand/35',
                            )}
                            aria-label={`${bar.label} ${yen(bar.amount)}`}
                            role="img"
                        />
                    </div>
                    <span className="flex-shrink-0 text-[10.5px] text-admin-ink-muted">
                        {bar.label}
                    </span>
                </li>
            ))}
        </ul>
    );
}
