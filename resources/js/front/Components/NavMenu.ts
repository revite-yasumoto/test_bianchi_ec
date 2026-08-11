export type NavMenuItem = {
    key: string;
    label: string;
    routeName: string;
};

/** ヘッダー（PC・SP共通）のナビゲーション項目。モックのヘッダーナビ5項目に対応する */
export const NAV_MENU: NavMenuItem[] = [
    { key: 'products', label: '商品一覧', routeName: 'products.index' },
    { key: 'news', label: '新着ニュース', routeName: 'news.index' },
    { key: 'guide', label: '買い物ガイド', routeName: 'guide' },
    { key: 'mypage', label: 'マイページ', routeName: 'mypage.index' },
    { key: 'contact', label: 'お問い合わせ', routeName: 'contact' },
];

export type FooterColumn = {
    key: string;
    heading: string;
    links: NavMenuItem[];
};

/** フッターの3カラム。モックの footCols に対応する */
export const FOOTER_COLUMNS: FooterColumn[] = [
    {
        key: 'shopping',
        heading: 'SHOPPING',
        links: [
            { key: 'products', label: '商品一覧', routeName: 'products.index' },
            { key: 'cart', label: 'カート', routeName: 'cart.index' },
            { key: 'mypage', label: 'マイページ', routeName: 'mypage.index' },
            {
                key: 'favorites',
                label: 'お気に入り',
                routeName: 'mypage.favorites',
            },
        ],
    },
    {
        key: 'support',
        heading: 'SUPPORT',
        links: [
            { key: 'guide', label: '買い物ガイド', routeName: 'guide' },
            { key: 'contact', label: 'お問い合わせ', routeName: 'contact' },
            { key: 'news', label: '新着ニュース', routeName: 'news.index' },
            {
                key: 'notices',
                label: '重要なお知らせ',
                routeName: 'notices.index',
            },
        ],
    },
    {
        key: 'legal',
        heading: 'LEGAL',
        links: [
            {
                key: 'tokushoho',
                label: '特定商取引法に基づく表記',
                routeName: 'legal.tokushoho',
            },
            {
                key: 'privacy',
                label: 'プライバシーポリシー',
                routeName: 'legal.privacy',
            },
            { key: 'terms', label: '利用規約', routeName: 'legal.terms' },
        ],
    },
];
