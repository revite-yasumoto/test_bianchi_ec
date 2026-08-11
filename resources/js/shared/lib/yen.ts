/** 金額を `¥12,345` 形式に整形する */
export function yen(amount: number): string {
    return `¥${amount.toLocaleString('ja-JP')}`;
}
