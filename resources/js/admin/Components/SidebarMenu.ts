export type SidebarMenuItem = {
    key: string;
    label: string;
    routeName?: string;
    children?: SidebarMenuItem[];
};

/**
 * 各項目の routeName は対応する単位が実装した時点で有効なルート名になる。
 * 未実装の間は route().has() が false を返すため、Sidebar 側で自動的に無効表示になる。
 */
export const SIDEBAR_MENU: SidebarMenuItem[] = [
    { key: 'dashboard', label: 'ダッシュボード', routeName: 'admin.dashboard' },
    { key: 'orders', label: '注文管理', routeName: 'admin.orders.index' },
    {
        key: 'products',
        label: '商品管理',
        children: [
            {
                key: 'products.index',
                label: '商品一覧',
                routeName: 'admin.products.index',
            },
            {
                key: 'products.create',
                label: '商品登録',
                routeName: 'admin.products.create',
            },
            {
                key: 'spec-options',
                label: '規格管理',
                routeName: 'admin.spec-options.index',
            },
            {
                key: 'categories',
                label: 'カテゴリ管理',
                routeName: 'admin.categories.index',
            },
            {
                key: 'products.csv',
                label: '商品CSV登録',
                routeName: 'admin.products.csv.index',
            },
            { key: 'stocks', label: '在庫', routeName: 'admin.stocks.index' },
        ],
    },
    { key: 'members', label: '会員マスタ', routeName: 'admin.members.index' },
    { key: 'admins', label: '管理者マスタ', routeName: 'admin.admins.index' },
    {
        key: 'shipping-settings',
        label: '送料設定マスタ',
        routeName: 'admin.shipping-settings.index',
    },
    {
        key: 'ec-settings',
        label: 'EC基本設定',
        routeName: 'admin.ec-settings.edit',
    },
    { key: 'news', label: '新着ニュース管理', routeName: 'admin.news.index' },
    {
        key: 'notices',
        label: '重要なお知らせ管理',
        routeName: 'admin.notices.index',
    },
    {
        key: 'contacts',
        label: 'お問い合わせ管理',
        routeName: 'admin.contacts.index',
    },
];
