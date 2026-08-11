/**
 * ステータス表示の文字色／背景色の対。
 * Tailwind のトークンでは表せない一回限りの淡色背景のため、モック（zip内 EC Demo Front.dc.html）の実値を定数として持つ。
 */
export type Tone = {
    fg: string;
    bg: string;
};

export const TONE = {
    /** 在庫あり・出荷済みなど進行中／良好な状態 */
    positive: { fg: '#2b6f64', bg: '#E4F2EF' },
    /** 在庫切れ・キャンセルなど停止／注意の状態 */
    negative: { fg: '#8a4030', bg: '#FBE7E1' },
    /** 新商品・受付中など情報の状態 */
    info: { fg: '#2F6F86', bg: '#E7F0F4' },
    /** お知らせなど注意喚起の状態 */
    warning: { fg: '#B0521F', bg: '#FDF0E2' },
} as const satisfies Record<string, Tone>;

export type ToneName = keyof typeof TONE;
