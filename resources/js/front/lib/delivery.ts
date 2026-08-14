const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

function label(date: Date): string {
    return `${date.getMonth() + 1}月${date.getDate()}日（${WEEKDAYS[date.getDay()]}）`;
}

/**
 * タイムゾーンによる日付のずれを避けるため、`new Date(文字列)` ではなく年月日を分解して組み立てる。
 * 日に加算値を渡すと月末をまたいでも自動で繰り上がる。
 */
function parseIsoDate(isoDate: string, addDays = 0): Date {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(year, month - 1, day + addDays);
}

/** `2026-08-16` を `8月16日（土）` に整形する */
export function japaneseDateLabel(isoDate: string): string {
    return label(parseIsoDate(isoDate));
}

/**
 * 基準日にお届け日数を暦日で加算した配達予定日のラベル。
 * 配送先を切り替えたときの即時再計算に使う（確定値はサーバー側で再算出する）。
 */
export function deliveryDateLabel(
    baseIsoDate: string,
    deliveryDays: number,
): string {
    return label(parseIsoDate(baseIsoDate, deliveryDays));
}
