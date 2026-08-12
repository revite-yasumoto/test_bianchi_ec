import { yen } from '@/shared/lib/yen';

/** 送料無料までの案内文。カートページとカートドロワーで文言を揃える */
export function freeShippingMessage(remaining: number) {
    return remaining <= 0
        ? '送料無料が適用されます'
        : `あと ${yen(remaining)} で送料無料`;
}

/** 送料無料までの達成率（0〜100） */
export function freeShippingProgress(remaining: number, threshold: number) {
    return remaining <= 0
        ? 100
        : Math.round(((threshold - remaining) / threshold) * 100);
}
