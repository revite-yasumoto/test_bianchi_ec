/**
 * 商品画像が未登録のときに使うカテゴリ別のグラデーション。
 * モックが商品ごとに持つ tint をカテゴリ単位に集約したもの。
 * 明色は `@theme` のトークンを参照する。暗色側はトークン化されていない中間色のため実値で持つ。
 */
const CATEGORY_TINTS: Record<string, string> = {
    ロードバイク:
        'linear-gradient(135deg,var(--color-brand),var(--color-brand-deep))',
    MTB: 'linear-gradient(135deg,var(--color-teal),#2b6f64)',
    シティ: 'linear-gradient(135deg,var(--color-amber),#c8871f)',
    eバイク: 'linear-gradient(135deg,var(--color-rose),#8f3e60)',
    パーツ: 'linear-gradient(135deg,var(--color-ink2),#3b444d)',
    アパレル: 'linear-gradient(135deg,var(--color-coral),#b64530)',
};

/** 未登録カテゴリ用。カテゴリ名から決まる1色を選び、同じカテゴリでは常に同じ配色にする */
const FALLBACK_TINTS: string[] = [
    'linear-gradient(135deg,var(--color-brand),var(--color-brand-deep))',
    'linear-gradient(135deg,var(--color-teal),#2b6f64)',
    'linear-gradient(135deg,var(--color-ink2),#3b444d)',
    'linear-gradient(135deg,var(--color-coral),#b64530)',
    'linear-gradient(135deg,var(--color-rose),#8f3e60)',
    'linear-gradient(135deg,var(--color-ink),#4a4a4a)',
];

export function categoryTint(categoryName: string): string {
    const tint = CATEGORY_TINTS[categoryName];

    if (tint) {
        return tint;
    }

    const seed = Array.from(categoryName).reduce(
        (total, character) => total + (character.codePointAt(0) ?? 0),
        0,
    );

    return FALLBACK_TINTS[seed % FALLBACK_TINTS.length];
}
