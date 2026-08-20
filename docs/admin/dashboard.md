# ダッシュボード

本ドキュメントは、管理画面ダッシュボードの**集計定義の正本**である。

## 機能概要

- **対象画面・機能の目的:** 管理者のログイン後の入口。売上・注文状況のサマリー、直近7日間の売上推移、最新の注文を1画面で確認する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。ログイン成功時の遷移先はこのルート（[docs/admin/auth.md](auth.md) 参照）。
- **本ドキュメントのスコープ:** ダッシュボードの表示と集計。注文の一覧・詳細は [docs/admin/order-index.md](order-index.md) / [docs/admin/order-show.md](order-show.md) が正本。

## 使用テーブル

`orders` と `contacts`。定義は [docs/2_database.md](../2_database.md) が正本。

`orders` から参照する列は `ordered_at` / `status` / `total` / `order_number` / `customer_name` で、いずれも注文時点のスナップショット。`order_items` や `products` は参照しない。`contacts` は未対応件数の集計にのみ使い、`status` 列だけを参照する。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+--------------+--------------+--------------+--------------+--------------+
| 本日の売上    | 今月の売上    | 新規注文      | 入金待ち      | 未対応の      |
| ¥352,000     | ¥1,690,000   | 5件           | 2件           | お問い合わせ  |
| 前日比 +78%   | 8月1日〜12日  | 本日 受付分   | 要確認        | 3件 / 要対応  |
+--------------+--------------+--------------+--------------+--------------+
+---------------------------+  +---------------------------+
| 売上推移（直近7日間）      |  | 最新の注文                 |
|      ▁ ▃ ▅ ▂ ▆ ▄ █       |  | BNC-… 山田 [入金待ち] ¥… 詳細|
|     8/6 … 8/12            |  | …（5件）                   |
+---------------------------+  +---------------------------+
```

- サマリーは `repeat(auto-fit, minmax(180px, 1fr))` のグリッドで、画面幅に応じて折り返す。下段の2カードは `minmax(320px, 1fr)` で、狭い幅では縦積みになる。
- サマリーの数値の配色は、売上が本文色、新規注文がブランド色、入金待ちと未対応のお問い合わせが警告色。
- 「未対応のお問い合わせ」のカードはお問い合わせ一覧（`admin.contacts.index`）の未対応の絞り込みへ遷移する。
- 棒グラフはCSSのみで描画し、グラフライブラリを導入しない。棒の高さは期間中の最大値を100%とした比率で、最終日（本日）のみブランド色で塗る。棒の上の金額は1万円以上を「35.2万」形式で表示する。
- 最新の注文の「詳細」は注文詳細（`admin.orders.show`）へ遷移する。0件のときは「注文がありません」を表示する。
- 期間の切り替えUIは持たない（直近7日間固定）。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type Props = {
    summary: {
        today_sales: number;
        today_sales_note: string;      // '前日比 +78%' または '前日実績なし'
        month_sales: number;
        month_sales_note: string;      // '8月1日〜12日'
        new_order_count: number;
        awaiting_payment_count: number;
        unhandled_contact_count: number;
    };
    chart: { label: string; amount: number }[];   // 直近7日間（古い順）。label は 'M/D'
    latestOrders: {
        id: number;
        order_number: string;
        customer_name: string;
        total: number;
        status: string;                // OrderStatus の値
        status_label: string;
        status_tone: Tone;             // バッジの配色（OrderStatus::color()）
    }[];
};
```

- **入力値バリデーションルール:** なし（入力を受け取らない）。

- **集計の定義（`DashboardSummaryService`）:**

| 項目 | 集計方法 |
|---|---|
| 本日の売上 | `ordered_at` が本日、かつ `status != cancelled` の `total` 合計 |
| 前日比 | （本日の売上 − 前日の売上）÷ 前日の売上 × 100 を四捨五入し `前日比 +N%` 形式にする。前日の売上が0なら `前日実績なし` |
| 今月の売上 | `ordered_at` が当月、かつ `status != cancelled` の `total` 合計 |
| 今月の売上の補足 | `N月1日〜N日`（当月1日から本日まで） |
| 新規注文 | `ordered_at` が本日の件数（キャンセルを含む） |
| 入金待ち | `status = awaiting_payment` の件数（日付を問わない） |
| 未対応のお問い合わせ | `contacts.status = unhandled` の件数（日付・タブを問わない） |
| 売上推移 | 本日を含む直近7日間の日別 `total` 合計（`status != cancelled`）。注文がない日は0 |
| 最新の注文 | `ordered_at` の降順 → IDの降順で5件（ステータスを問わない） |

- **主要な処理フロー:** `index()` が `DashboardSummaryService` の `summary()` / `salesChart()` / `latestOrders()` を呼び、結果をそのままpropsとして返す。

- **集計クエリの実装方針:**
  - 日別集計は `DATE(ordered_at)` を別名で `SELECT` し同じ別名で `GROUP BY` する。`SELECT` リストに `GROUP BY` に無い非集約列を置かない（MySQL の `ONLY_FULL_GROUP_BY` は 5.7 以降デフォルトで有効）。
  - 7日分の日付の穴埋めはPHP側で行い、SQLで日付マスタを生成しない。
  - サマリーの各件数・合計は個別のクエリで取得する。
  - `orders.total` は整数列のため、集計結果も整数として扱う。

## 業務ルール

- 売上は税込金額（送料・代引き手数料を含む `total`）で集計し、入金状況を問わず計上する（キャンセルのみ除外）。
- キャンセル注文は売上から除外するが、「新規注文」の件数には含める（本日受け付けた注文の総数を表すため）。
- 集計結果はキャッシュせず、リクエストごとに算出する。

## 関連ドキュメント

- [docs/admin/auth.md](auth.md) — ログイン成功時の遷移先の決定（`landingUrl()`）の正本
- [docs/admin/order-index.md](order-index.md) — 注文一覧。ステータスのラベル・配色の扱いを共有する
- [docs/admin/order-show.md](order-show.md) — 「最新の注文」の遷移先
- [docs/admin/contact.md](contact.md) — お問い合わせ管理の正本。未対応件数のカードの遷移先
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`StatusBadge`・`Badge` の正本
- [docs/2_database.md](../2_database.md) — `orders` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/DashboardController.php` |
| Service | `app/Services/Admin/Dashboard/DashboardSummaryService.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Dashboard/Index.tsx` |
| Component | `resources/js/admin/Components/Dashboard/SummaryCard.tsx` |
| Component | `resources/js/admin/Components/Dashboard/SalesBarChart.tsx` |
| Component | `resources/js/admin/Components/Dashboard/LatestOrderTable.tsx` |
| Test | `tests/Feature/Admin/Dashboard/DashboardTest.php` |
| Test | `tests/Feature/Admin/Dashboard/DashboardSummaryTest.php` |
| Test | `tests/Feature/Admin/Dashboard/SalesChartTest.php` |
| Test | `tests/Feature/Admin/Dashboard/LatestOrdersTest.php` |

## 受け入れ条件

- ログイン済みの管理者がダッシュボードを表示できる: `tests/Feature/Admin/Dashboard/DashboardTest.php`（`ログイン済みの管理者はダッシュボードを表示できる`）
- 未認証はログイン画面へリダイレクトされ、ログイン成功後はダッシュボードへ遷移する: 同上（`未認証はログイン画面へリダイレクトされる`・`ログイン成功後はダッシュボードへ遷移する`）
- 本日・今月の売上がキャンセルを除外し対象期間のみを集計する: `tests/Feature/Admin/Dashboard/DashboardSummaryTest.php`（`本日の売上はキャンセル注文を除いて合計される`・`今月の売上は当月の注文のみを集計する`）
- 前日比・前日実績なしの出し分け: 同上（`前日に売上があるときは前日比が表示される`・`前日の売上が0のときは前日実績なしと表示される`）
- 新規注文がキャンセルを含む本日受付分になり、入金待ちが日付によらず集計される: 同上（`新規注文の件数はキャンセルを含む本日受付分になる`・`入金待ちの件数は日付によらず集計される`）
- 注文がないとき各値が0になる: 同上（`注文がないときはすべて0になる`）
- 未対応のお問い合わせ件数が対応中・対応済みを除いて集計され、問い合わせが無いときは0になる: `tests/Feature/Admin/Dashboard/DashboardSummaryTest.php` に実装時に追加するFeatureテスト
- 未対応のお問い合わせのカードからお問い合わせ一覧へ遷移できる: 目視確認
- 売上推移が直近7日間を古い順に返し、注文がない日を0で埋める: `tests/Feature/Admin/Dashboard/SalesChartTest.php`（`直近7日間が古い順に並ぶ`・`注文がない日は0で埋められる`）
- 同日の複数注文が合算されキャンセルが除外され、期間外が含まれない: 同上（`同じ日の複数注文は合算されキャンセルは除外される`・`期間外の注文は含まれない`）
- 月をまたぐ期間でも日ごとに集計される: 同上（`月をまたぐ期間でも日ごとに集計される`）
- 最新の注文が注文日の降順で5件返り、5件未満なら件数分だけ返る: `tests/Feature/Admin/Dashboard/LatestOrdersTest.php`（`最新の注文が注文日の降順で5件返る`・`注文が5件未満のときは件数分だけ返る`）
- 氏名・合計金額・ステータスが渡され、キャンセル注文も表示される: 同上（`氏名と合計金額とステータスが渡される`・`キャンセル注文も最新の注文に表示される`）
- 棒グラフの描画・サマリーのレスポンシブ表示: 自動テストなし。目視確認で担保する
