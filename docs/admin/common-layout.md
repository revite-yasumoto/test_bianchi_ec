# 管理画面共通レイアウト・共通UIコンポーネント

本ドキュメントは管理画面共通レイアウト（サイドバー・ヘッダー・コンテンツ枠・トースト）と、以降の管理画面が共通利用するUIコンポーネント（`FilterBar` / `DataTable` / `ConfirmDialog`）の**正本**。単位04以降の各管理画面仕様書は、これらの構成・Props型を再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** 管理画面（`/admin/*`）の全ページが共通で使うレイアウトとUIパーツを提供する。
- **URL / メソッド:** 該当なし（画面固有のルートを持たないレイアウト・コンポーネント群）。
- **アクセス権限・ミドルウェア:** `AdminLayout` はログイン後（`auth:admin` 配下）のページから利用される前提。認証そのものは [docs/admin/auth.md](auth.md) が正本。
- **本ドキュメントのスコープ:** レイアウト・共通UIコンポーネントの構成とPropsのみ。各画面固有の実装（一覧・登録フォーム等）は各単位の仕様書に委ねる。

## 使用テーブル

なし。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+----------+--------------------------------------------+
| Bianchi  |  [ページタイトル]           [ヘッダーアクション] |
| ADMIN    +--------------------------------------------+
| CONSOLE  |                                              |
|----------|  コンテンツ（各画面固有）                      |
| ダッシュ  |                                              |
| ボード    |                                              |
| 注文管理  |                                              |
| ▾商品管理 |                                              |
|  商品一覧 |                                              |
|  商品登録 |                                              |
|  規格管理 |                                              |
|  ...     |                                              |
| 会員マスタ|                                              |
| ...      |                                              |
|----------|                                              |
| 管理者名  |                                              |
| メール    | [ログアウト]                                  |
+----------+--------------------------------------------+
                    [トースト（下部中央・2.2秒で自動消去）]
```

- サイドバー幅: `w-admin-sidebar`（236px固定）。
- グループ（商品管理）の開閉状態は、現在のページがそのグループの子項目に属する場合はデフォルトで開く。それ以外は閉じた状態で初期化される。
- サイドメニューの各項目は対応するルート名が存在する（`route().has()` が `true`）場合のみリンクとして機能し、それ以外は非活性表示（クリック不可・淡色）になる。単位04以降が対応するルートを実装すると、コード変更なしに自動的にリンクとして有効化される。
- トーストは画面下部中央に固定表示され、2200ms後に自動的に消える。

## インターフェース ＆ データロジック

### デザイントークン（`resources/css/app.css` `@theme`）

| トークン | 値 | 用途 |
|---|---|---|
| `--color-admin-brand` | `#2f6f86` | ボタン・アクティブメニューの主色 |
| `--color-admin-brand-deep` | `#274f60` | ログイン画面グラデーション・サイドバーロゴ |
| `--color-admin-brand-darkest` | `#1b3844` | ログイン画面グラデーション終端 |
| `--color-admin-ink` | `#232323` | 本文文字色 |
| `--color-admin-ink-muted` | `#5e6b77` | 補助文字色 |
| `--color-admin-bg` | `#ede8e0` | ページ全体の背景 |
| `--color-admin-sidebar-bg` | `#f7f6f3` | サイドバー・テーブルヘッダー背景 |
| `--color-admin-surface` | `#fcfbf9` | コンテンツ・ログインフォーム背景 |
| `--color-admin-line` | `#e7dfd2` | 罫線・ボーダー |
| `--color-admin-danger` | `#e1664b` | エラー文字・破壊的操作の強調色 |
| `--width-admin-sidebar` | `236px` | サイドバー幅 |

配色は `docs/Markdown file UI mockups.zip` 内 `EC Demo Admin.dc.html`（ブランド表記 `VELOCE`）の実値を、`Bianchi` ブランドに読み替えて採用したもの。

フォント（`--font-sans` / `--font-jp` / `--font-mono`）はフロントと共通で、[docs/front/common-layout.md](../front/common-layout.md) が正本。管理画面の本文書体も共通テンプレート（`resources/views/app.blade.php`）の指定に従う。

### `AdminLayout`（`resources/js/admin/Layouts/AdminLayout.tsx`）

```ts
type AdminLayoutProps = {
    title: string;
    headerActions?: ReactNode;
    children: ReactNode;
};
```

- `title` / `headerActions` は内部の `PageHeader` にそのまま渡す。
- トースト表示は React Context（`AdminToastContext`）で提供する。子ページは `useAdminToast().showToast(message)` を呼ぶ（`AdminLayout` の外で呼ぶと例外を投げる）。

### `Sidebar`（`resources/js/admin/Components/Sidebar.tsx`）

- Props なし。`usePage<AdminSharedProps>().props.auth.admin` から管理者名・メールアドレスを取得して表示する。
- ログアウトは `ConfirmDialog` での確認後に `router.post(route('admin.logout'))` を実行する。

### `SidebarMenu`（`resources/js/admin/Components/SidebarMenu.ts`）

```ts
type SidebarMenuItem = {
    key: string;
    label: string;
    routeName?: string;
    children?: SidebarMenuItem[];
};
```

`SIDEBAR_MENU` に全10メニュー分の項目を定義する（グループを除く実項目数は15）。

| 表示名 | 子項目 | `routeName` |
|---|---|---|
| ダッシュボード | - | `admin.dashboard` |
| 注文管理 | - | `admin.orders.index` |
| 商品管理（グループ） | 商品一覧 `admin.products.index` / 商品登録 `admin.products.create` / 規格管理 `admin.spec-options.index` / カテゴリ管理 `admin.categories.index` / 商品CSV登録 `admin.products.csv` / 在庫 `admin.stocks.index` | - |
| 会員マスタ | - | `admin.members.index` |
| 管理者マスタ | - | `admin.admins.index` |
| 送料設定マスタ | - | `admin.shipping-settings.index` |
| EC基本設定 | - | `admin.ec-settings.edit` |
| 新着ニュース管理 | - | `admin.news.index` |
| 重要なお知らせ管理 | - | `admin.notices.index` |

各項目のルート名は、対応する単位がその名前でルートを実装することを前提に本書が定めたもの。単位04以降で異なる名前を採用する場合は、実装側で本テーブルと `SidebarMenu.ts` の両方を更新する。

### `PageHeader`（`resources/js/admin/Components/PageHeader.tsx`）

```ts
type PageHeaderProps = { title: string; actions?: ReactNode };
```

### `FilterBar`（`resources/js/admin/Components/FilterBar.tsx`）

```ts
type FilterBarProps = { resultCount: number; totalCount?: number; onClear: () => void; children: ReactNode };
```

白カード内に絞り込み入力（`children`）を横並びに配置し、右端に件数表示とクリアボタンを共通設置する。`totalCount` を渡すと「N件 / 全M件」の形式になり、省略すると「N件」のみを表示する。

### `Pagination`（`resources/js/admin/Components/Pagination.tsx`）

```ts
type PaginationProps = { links: { url: string | null; label: string; active: boolean }[] };
```

Laravel の `paginate()` が返す `links` をそのまま渡す。1ページに収まる場合（リンクが3件以下）は何も描画しない。`&laquo; Previous` 等の既定ラベルは「前へ」「次へ」に置き換える。

### `DataTable`（`resources/js/admin/Components/DataTable.tsx`）

```ts
type Column<T> = { key: string; header: string; render: (row: T) => ReactNode; className?: string };
type DataTableProps<T> = { columns: Column<T>[]; rows: T[]; rowKey: (row: T) => string | number; emptyMessage?: string };
```

`rows` が0件の場合は罫線付きカード内にメッセージ（既定「該当するデータがありません」）を表示する。横スクロール対応（`overflow-x-auto`）。

### `MasterListCard`（`resources/js/admin/Components/MasterListCard.tsx`）

```ts
type MasterListRow = { id: number; name: string; note?: string };

type MasterListCardProps = {
    title?: string;
    description: string;
    rows: MasterListRow[];
    storeRouteName: string;
    storeParams?: Record<string, string>;
    destroyRouteName: string;
    placeholder: string;
    addedMessage: string;
    deletedMessage: string;
    deleteNote: string;
    className?: string;
};
```

名称の一覧と末尾の追加フォームを1枚の白カードにまとめたマスタ編集用のカード。追加（`useForm`）・削除（`router.delete` ＋ `ConfirmDialog`）・成功時のトースト表示までをカード内で完結させる。`storeParams` は追加時に `name` と一緒に送る固定値（規格管理の `type` 等）。削除に失敗した場合はサーバーが返す `delete` キーのメッセージをトーストで表示する。

利用画面: [docs/admin/category.md](category.md)（カテゴリ管理）、[docs/admin/spec-option.md](spec-option.md)（規格管理）。

### `ConfirmDialog`（`resources/js/admin/Components/ConfirmDialog.tsx`）

```ts
type ConfirmDialogProps = {
    isOpen: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    confirmVariant?: 'default' | 'danger';
    onConfirm: () => void;
    onCancel: () => void;
};
```

`Escape` キーでも `onCancel` を実行する。

## 業務ルール

- サイドメニューの未実装リンクは、押せる状態のまま残さない（`route().has()` が `false` の間は非活性表示にする）。各単位が対応するルートを実装した時点で、`SidebarMenu.ts` の変更なしに自動的に有効化される。
- 管理画面はPC専用とし、レスポンシブ対応は行わない（サイドバー幅固定・折りたたみ機構なし）。モックHTML（`EC Demo Admin.dc.html`）にも管理画面側のメディアクエリは存在せず、フロント（会員向け）のみモバイル対応する。

## 関連ドキュメント

- [docs/admin/auth.md](auth.md) — 管理者ログイン・ログアウトの正本。`AdminSharedProps` の型定義はこちらが正本
- [docs/front/common-layout.md](../front/common-layout.md) — フロント共通レイアウトの正本。フォント構成と共通UIコンポーネント（`resources/js/shared/`）はこちらが正本
- [docs/admin/category.md](category.md) — `MasterListCard` の利用画面
- [docs/admin/spec-option.md](spec-option.md) — `MasterListCard` の利用画面
- [docs/1_system_overview.md](../1_system_overview.md) — ブランド表記・技術構成の前提

## ソースファイル

| 種別 | パス |
|---|---|
| Layout | `resources/js/admin/Layouts/AdminLayout.tsx` |
| Component | `resources/js/admin/Components/Sidebar.tsx` |
| Component | `resources/js/admin/Components/SidebarMenu.ts` |
| Component | `resources/js/admin/Components/PageHeader.tsx` |
| Component | `resources/js/admin/Components/FilterBar.tsx` |
| Component | `resources/js/admin/Components/DataTable.tsx` |
| Component | `resources/js/admin/Components/ConfirmDialog.tsx` |
| Component | `resources/js/admin/Components/MasterListCard.tsx` |
| Component | `resources/js/admin/Components/Pagination.tsx` |
| 型 | `resources/js/types/global.d.ts` |
| スタイル | `resources/css/app.css` |

## 受け入れ条件

本機能を担保する自動テストは存在しない（フロントエンドのユニットテスト基盤は本プロジェクトに未導入）。以下は型チェック（`npx tsc --noEmit`）と目視確認で担保する。

- サイドバーの全10メニューが表示され、未実装項目は非活性表示になる: 目視確認
- 実装済み項目（現時点ではサイドメニューのうち商品一覧・商品登録・カテゴリ管理・規格管理・在庫）へのリンクが機能する: 目視確認
- グループの開閉、開閉時のデフォルト状態: 目視確認
- ログアウト確認モーダルの表示・キャンセル・`Escape`キーでの閉じる動作: 目視確認
- トーストの表示位置・自動消去（2.2秒）: 目視確認
- レスポンシブ表示（PC・SP）: 目視確認
- Props型定義の整合性: `npx tsc --noEmit`
