# 単位12: 管理画面 - ダッシュボード

依存: 単位07

管理画面のログイン後の入口。集計はすべて `orders` のスナップショット列から行う。

## スコープ

- サマリーカード4枚: 本日の売上・今月の売上・新規注文（本日受付分）・入金待ち
- 売上推移グラフ（直近7日間の棒グラフ）
- 最新の注文一覧（5件）→ 注文詳細へ
- 管理者ログイン後の遷移先を `admin.dashboard` に変更する

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / Service） | `laravel-set:laravel` |
| 集計クエリ（`GROUP BY` を含む） | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | ダッシュボード |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/DashboardController.php` | `index()` |
| Service | `app/Services/Admin/Dashboard/DashboardSummaryService.php` | サマリー4項目・売上推移7日分・最新注文5件を集計 |
| Controller | `app/Http/Controllers/Admin/Auth/AuthenticatedSessionController.php` | **修正**: ログイン後の遷移先を `admin.dashboard` に変更 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Dashboard/Index.tsx` | サマリーカード4枚＋売上推移グラフ＋最新注文の2カラム |
| Component | `resources/js/admin/Components/Dashboard/SummaryCard.tsx` | ラベル・値・補足の白カード |
| Component | `resources/js/admin/Components/Dashboard/SalesBarChart.tsx` | CSSのみで描画する棒グラフ（外部グラフライブラリを使わない） |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「ダッシュボード」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
type Props = {
  summary: {
    today_sales: number;
    today_sales_note: string;        // '前日比 +78%' または '前日実績なし'
    month_sales: number;
    month_sales_note: string;        // '8月1日〜6日'
    new_order_count: number;         // 本日受付分
    awaiting_payment_count: number;
  };
  chart: { label: string; amount: number }[];   // 直近7日間（古い順）。label は 'M/D'
  latestOrders: {
    id: number;
    order_number: string;
    customer_name: string;
    total: number;
    status: OrderStatus;
    status_label: string;
  }[];
};
```

### 集計の定義

| 項目 | 集計方法 |
|---|---|
| 本日の売上 | `orders` のうち `ordered_at` が本日、かつ `status != cancelled` の `total` 合計 |
| 前日比 | （本日の売上 - 前日の売上）÷ 前日の売上 × 100 を四捨五入。前日の売上が0なら「前日実績なし」 |
| 今月の売上 | `orders` のうち `ordered_at` が当月、かつ `status != cancelled` の `total` 合計 |
| 新規注文 | `orders` のうち `ordered_at` が本日の件数（キャンセルを含む） |
| 入金待ち | `orders` のうち `status = awaiting_payment` の件数 |
| 売上推移 | 本日を含む直近7日間について、日ごとの `total` 合計（`status != cancelled`）。データがない日は0 |
| 最新の注文 | `ordered_at` の降順で5件 |

### 集計クエリの実装方針（`db-mysql:mysql` 適用結果）

- 売上推移の日別集計は `selectRaw('DATE(ordered_at) as ordered_date, SUM(total) as amount')` + `groupBy('ordered_date')` とする。**`SELECT` リストに `GROUP BY` に無い非集約列を置かない**（`ONLY_FULL_GROUP_BY` は MySQL 5.7 以降デフォルトで有効）
- 7日分の日付の穴埋めは PHP 側で行う（SQL で日付マスタを生成しない）
- サマリーの各件数・合計は個別のクエリで取得する（1本の複雑なクエリにまとめない）
- `orders.total` は整数列なので、比較値・集計結果も整数として扱う

## 業務ルール

- 集計は `orders` のスナップショット列（`total` / `status` / `ordered_at` / `customer_name`）のみを参照する。`order_items` や `products` を JOIN しない
- キャンセル注文は売上の集計から除外する。「新規注文」の件数にはキャンセルを含める（本日受付た注文の総数を表すため）
- 売上は税込金額（送料・代引き手数料を含む `total`）で集計する

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Dashboard/DashboardTest.php` | 未認証は `admin.login` にリダイレクトされること／ログイン後に `admin.dashboard` へ遷移すること |
| `tests/Feature/Admin/Dashboard/DashboardSummaryTest.php` | 本日の売上がキャンセル注文を除外して合計されること／今月の売上が当月分のみを集計すること／前日実績が0のとき「前日実績なし」を返すこと／入金待ち件数／新規注文件数がキャンセルを含むこと（`Carbon::setTestNow()` で日付を固定する） |
| `tests/Feature/Admin/Dashboard/SalesChartTest.php` | 直近7日間が古い順に7要素返ること／注文がない日が0で埋まること／月をまたぐ期間でも正しく集計されること |
| `tests/Feature/Admin/Dashboard/LatestOrdersTest.php` | 注文日の降順で5件返ること／5件未満のとき件数分だけ返ること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **売上の定義**: 要件は「売上」とだけ記載。推奨=**`orders.total`（送料・代引き手数料を含む税込金額）の合計、キャンセル注文を除外**。商品代金のみで集計したい場合は実行時に指示すること。
2. **キャンセル注文の扱い**: 推奨=売上からは除外し、「新規注文」の件数には含める。
3. **入金確認前の注文の売上計上**: 推奨=**ステータスを問わず計上する**（キャンセルのみ除外）。入金確認済み以降のみを売上とする会計方針を採る場合は実行時に指示すること。
4. **グラフライブラリ**: 推奨=**導入しない**。CSSのみで棒グラフを描画する（モックも同じ方式）。
5. **集計のキャッシュ**: 実装しない（デモ規模のためリクエストごとに集計する）。
6. **期間の切り替えUI**: 実装しない（直近7日間固定）。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で `docs/admin/dashboard.md` を作成する（集計定義の**正本**）
- `docs/admin/auth.md` の「ログイン後の遷移先」を `admin.dashboard` に同期する
- 本計画ファイルを削除し、トラッカーの状態を更新する
