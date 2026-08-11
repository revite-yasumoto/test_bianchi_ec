# 注文一覧

## 機能概要

- **対象画面・機能の目的:** 注文の一覧・絞り込みと、注文詳細への導線。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/orders` | `admin.orders.index` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 一覧の表示・絞り込み・ページネーション。注文詳細とステータス更新は [docs/admin/order-show.md](order-show.md) が正本。

## 使用テーブル

`orders` のみを参照する。定義は [docs/2_database.md](../2_database.md) が正本。

顧客名・金額・支払方法はすべて注文時点のスナップショット列であり、`users` や `products` は結合しない。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| ステータス      | 注文番号・顧客名          |[クリア]  6件 / 全24件 |
+------------------------------------------------------------------+
| 注文番号      |注文日     |顧客名  |金額     |支払方法 |ステータス|    |
|---------------|-----------|--------|---------|---------|---------|----|
| BNC-2607-0918 |2026.07.24 |山田 太郎| ¥18,000 |銀行振込 |出荷済み |詳細|
| BNC-2606-0402 |2026.06.15 |佐藤 花子| ¥5,800  |代金引換 |キャンセル|詳細|
+------------------------------------------------------------------+
                         [前へ] [1] [2] [次へ]
```

- 絞り込みは入力から400ms後に反映し、条件はクエリストリングに保持する。詳細画面から戻っても条件が維持される。
- ステータスはバッジで表示する。配色は `App\Enums\OrderStatus::color()` が正本で、サーバーから `status_tone` として渡す。
- 該当0件のときは「該当する注文がありません」を表示する。
- CSVエクスポートのボタンは設置しない（CSV対応は商品・会員・管理者マスタのみ）。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type OrderRow = {
    id: number;
    order_number: string;
    ordered_at: string;          // 'YYYY.MM.DD'
    customer_name: string;       // スナップショット
    total: number;
    payment_method_label: string;
    status: string;
    status_label: string;
    status_tone: Tone;           // { fg, bg }
};

type Props = {
    orders: Paginated<OrderRow>;
    filters: { status: string; q: string | null };
    totalCount: number;          // 絞り込み前の全件数
    statusOptions: { value: string; label: string }[];
};
```

- **入力値バリデーションルール:** 絞り込みはフォーム送信ではないため FormRequest を使わない。`status` は `App\Enums\OrderStatus` の値の一覧と照合し、外れた値は `all` に丸める。

- **主要な処理フロー（`index()` と `BuildOrderFilter`）:**
1. 絞り込みを適用する。
   - `status`: 完全一致（`all` は条件なし）
   - `q`: `order_number` の部分一致 **または** `customer_name` の部分一致。`%` と `_` はエスケープする
2. `ordered_at` の降順（同時刻は `id` の降順）で1ページ50件のページネーションを行い、クエリストリングを引き継ぐ。

## 業務ルール

- 一覧は `orders` のスナップショット列のみで描画する。会員情報・商品情報が後から変更・削除されても表示は変わらない。
- 注文の編集・削除は実装しない。変更できるのはステータスのみ。

## 関連ドキュメント

- [docs/admin/order-show.md](order-show.md) — 注文詳細・ステータス遷移規則・在庫戻しの正本
- [docs/admin/common-layout.md](common-layout.md) — `FilterBar`・`DataTable`・`Pagination` の正本
- [docs/2_database.md](../2_database.md) — `orders` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/OrderController.php` |
| Action | `app/Actions/Admin/Order/BuildOrderFilter.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Order/Index.tsx` |
| Component | `resources/js/admin/Components/Order/StatusBadge.tsx` |
| Test | `tests/Feature/Admin/Order/OrderIndexTest.php` |

## 受け入れ条件

- 一覧が表示され、注文日が `YYYY.MM.DD` 形式になる: `tests/Feature/Admin/Order/OrderIndexTest.php`（`注文一覧が表示される`）
- 支払方法の表示名が渡る: 同上（`支払方法の表示名が渡される`）
- ステータス・注文番号・顧客名で絞り込める: 同上（`ステータスで絞り込める`・`注文番号で絞り込める`・`顧客名で絞り込める`）
- 複数条件を組み合わせられる: 同上（`ステータスと検索語を組み合わせて絞り込める`）
- 一覧が注文日の降順で並ぶ: 同上（`一覧は注文日の降順で並ぶ`）
- 該当0件で空の一覧になる: 同上（`該当がない場合は空の一覧になる`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 絞り込みのデバウンス・クエリストリングでの保持: 自動テストなし。目視確認で担保する
- ステータスバッジの配色: 自動テストなし。目視確認で担保する
