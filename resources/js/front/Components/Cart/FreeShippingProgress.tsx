import {
    freeShippingMessage,
    freeShippingProgress,
} from '@/front/lib/freeShipping';
import { cn } from '@/lib/utils';

type FreeShippingProgressProps = {
    /** 送料無料までの残額。0なら送料無料が適用済み */
    remaining: number;
    threshold: number;
};

/** 「あと〇〇円で送料無料」の案内と達成率のバー */
export function FreeShippingProgress({
    remaining,
    threshold,
}: FreeShippingProgressProps) {
    const percentage = freeShippingProgress(remaining, threshold);

    return (
        <div>
            <p
                className={cn(
                    'rounded-xl px-4 py-3.5 text-[12.5px] leading-[1.6] font-bold',
                    percentage === 100
                        ? 'bg-[#E4F2EF] text-[#2b6f64]'
                        : 'bg-white text-ink2',
                )}
            >
                {freeShippingMessage(remaining)}
            </p>
            <div
                role="progressbar"
                aria-label="送料無料までの達成率"
                aria-valuenow={percentage}
                className="mt-3 h-1.5 overflow-hidden rounded-full bg-line"
            >
                <div
                    style={{ width: `${percentage}%` }}
                    className="h-full rounded-full bg-teal transition-[width] duration-400"
                />
            </div>
        </div>
    );
}
