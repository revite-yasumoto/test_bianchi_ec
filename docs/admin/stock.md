# 在庫管理

## 機能概要

- **対象画面・機能の目的:** 在庫マスタをバリエーション（SKU）単位で一覧し、個別更新と表示中の一括更新を行う。棚卸しなどの手動調整のための画面。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/stocks` | `admin.stocks.index` | `auth:admin` |
| PUT | `/admin/stocks/bulk` | `admin.stocks.bulk-update` | `auth:admin` |
| PUT | `/admin/stocks/{stock}` | `admin.stocks.update` | `auth:admin` |

`stocks/bulk` は `stocks/{stock}` にマッチしてしまうため、個別更新より先に定義する。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 在庫の一覧・個別更新・一括更新。商品登録時の在庫入力は [docs/admin/product-form.md](product-form.md) が正本。

## 使用テーブル

`stocks` を起点に `product_variants` / `products` / `categories` を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

商品の属性（SKU有無・カテゴリ・商品名）で絞り込み・並べ替えを行うため、`product_variants` と `products` は結合して取得する。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| SKU区分 | カテゴリ | 商品名        |[クリア][表示中を一括更新] 12件/全40件|
+------------------------------------------------------------------+
| 商品名     |バリエーション|SKUコード |カテゴリ|現在庫|在庫数変更|    |
|------------|-------------|----------|--------|------|----------|----|
|チームジャージ|ネイビー / L |JS-2026-12|ウェア  | 0    | [   0  ] |更新|
|チームジャージ|ネイビー / M |JS-2026-11|ウェア  | 3    | [   3  ] |更新|
|ロードスター  |規格なし     |RC7-105   |ロード  | 12   | [  12  ] |更新|
+------------------------------------------------------------------+
                         [前へ] [1] [2] [次へ]
```

- 絞り込みは入力から400ms後に反映し、条件はクエリストリングに保持する。
- 現在庫はバッジで表示し、0のときは「在庫切れ」と表示する。
- 個別更新・一括更新はいずれも確認モーダル（`ConfirmDialog`）を経て実行し、完了後にトーストを表示する。
- 該当0件のときは「該当する在庫データがありません」を表示し、一括更新ボタンを無効化する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type StockRowData = {
    stock_id: number;
    product_name: string;
    category_name: string;
    variant_label: string;   // 'ネイビー / M' または '規格なし'
    sku_code: string;        // 取扱対象外は '-'
    has_sku: boolean;
    quantity: number;
};

type StockFilters = {
    has_sku: string;         // 'all' | 'with' | 'without'
    category_id: number | null;
    q: string | null;        // 商品名の部分一致
};

type StockManagerProps = {
    stocks: Paginated<StockRowData>;
    categories: { id: number; name: string }[];
    filters: StockFilters;
    totalCount: number;      // 絞り込み前の全在庫件数
};
```

- **入力値バリデーションルール（`Admin\Stock\UpdateStockRequest`）:**

| 項目 | ルール |
|---|---|
| `quantity` | 必須・整数・0以上・999,999以下 |

- **入力値バリデーションルール（`Admin\Stock\BulkUpdateStockRequest`）:**

| 項目 | ルール |
|---|---|
| `stocks` | 必須・配列・1要素以上・200要素以下 |
| `stocks.*.id` | 必須・整数・`stocks.id` に存在 |
| `stocks.*.quantity` | 必須・整数・0以上・999,999以下 |
| `has_sku` / `category_id` / `q` / `page` | 任意。更新対象の再検証に使う |

- **主要な処理フロー:**

**一覧（`index()` と `BuildStockFilter`）:**
1. `stocks` に `product_variants` と `products` を結合し、`variant.product.category` を eager load する。
2. 絞り込みを適用する。
   - `has_sku`: `products.has_sku` と一致（`all` は条件なし）
   - `category_id`: `products.category_id` と完全一致
   - `q`: `products.name` の部分一致。`%` と `_` はエスケープする
3. 商品名 → カラー → サイズ → 在庫IDの昇順で並べ、1ページ100件でページネーションする。カラー・サイズは名称の昇順で、規格管理の表示順は反映しない。
4. `variant_label` は `color_name` と `size_name` を ` / ` で連結する。どちらも `null` なら `規格なし`。

**個別更新（`update()`）:** 対象の `stocks.quantity` を更新して直前の画面へ戻る。

**一括更新（`bulkUpdate()`）:**
1. 送信された絞り込み条件・ページ番号で同じクエリを再実行し、得られた在庫IDの集合と送信されたIDの集合を照合する。
2. 一致しない場合は更新せず、「表示内容が変わっています。一覧を再読み込みしてからやり直してください。」を `stocks` のエラーとして返す。
3. 一致する場合は `BulkUpdateStocks` が1トランザクション内で各行を更新する。

## 業務ルール

- 在庫の減算は注文確定時（単位16）、加算は注文キャンセル時（単位07・17）に自動で行う。本画面は手動調整のためのもの。
- 在庫数の変更履歴は保持しない。
- フロント（会員向け）では在庫を「在庫あり／在庫切れ」の二値でのみ表示し、実数は公開しない。管理画面では実数を表示する。
- 一括更新の対象は現在表示中のページの行に限る。クライアントから任意のIDを送られても表示していない在庫が書き換わらないよう、サーバー側で絞り込み結果との一致を再検証する。

## 関連ドキュメント

- [docs/admin/product-form.md](product-form.md) — 商品登録時の在庫入力とバリエーション同期の正本
- [docs/admin/product-index.md](product-index.md) — 商品単位の在庫合計を表示する商品一覧
- [docs/admin/common-layout.md](common-layout.md) — `FilterBar`・`Pagination`・`ConfirmDialog` の正本
- [docs/2_database.md](../2_database.md) — `stocks`・`product_variants` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/StockController.php` |
| FormRequest | `app/Http/Requests/Admin/Stock/UpdateStockRequest.php` |
| FormRequest | `app/Http/Requests/Admin/Stock/BulkUpdateStockRequest.php` |
| Action | `app/Actions/Admin/Stock/BuildStockFilter.php` |
| Action | `app/Actions/Admin/Stock/BulkUpdateStocks.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Stock/Index.tsx` |
| Component | `resources/js/admin/Components/Stock/StockManager.tsx` |
| Component | `resources/js/admin/Components/Stock/StockRow.tsx` |
| Test | `tests/Feature/Admin/Stock/StockIndexTest.php` |
| Test | `tests/Feature/Admin/Stock/StockUpdateTest.php` |
| Test | `tests/Feature/Admin/Stock/StockBulkUpdateTest.php` |

## 受け入れ条件

- 一覧がバリエーション単位で表示され、並びがカラー→サイズの昇順になる: `tests/Feature/Admin/Stock/StockIndexTest.php`（`在庫一覧がバリエーション単位で表示される`）
- SKUなし商品が「規格なし」1行になる: 同上（`規格なし商品は1行で規格なしと表示される`）
- SKU有無・カテゴリ・商品名で絞り込める: 同上（`規格の有無で絞り込める`・`カテゴリで絞り込める`・`商品名で絞り込める`）
- 一覧が商品名の昇順で並ぶ: 同上（`一覧は商品名の昇順で並ぶ`）
- 行数に比例してクエリが増えない: 同上（`一覧の発行クエリ数は行数に比例しない`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 在庫数を更新できる（0も可）: `tests/Feature/Admin/Stock/StockUpdateTest.php`（`在庫数を更新できる`・`在庫数を0に更新できる`）
- 負数・上限超過・非数値・未入力は更新できない: 同上（`負の在庫数は更新できない`・`上限を超える在庫数は更新できない`・`数値以外の在庫数は更新できない`・`在庫数が未入力では更新できない`）
- 未認証は個別更新できない: 同上（`未認証は在庫を更新できない`）
- 表示中の全行を一括更新できる（絞り込みありも含む）: `tests/Feature/Admin/Stock/StockBulkUpdateTest.php`（`表示中の全行を一括更新できる`・`絞り込み条件付きでも表示中の行を一括更新できる`）
- 表示中に含まれないIDを送るとエラーになり更新されない: 同上（`表示中に含まれない在庫を送るとエラーになり更新されない`・`表示中の一部だけを送るとエラーになり更新されない`）
- 一部に不正な値があれば全件更新されない: 同上（`一部に不正な在庫数があれば全件更新されない`・`存在しない在庫を送ると更新できない`）
- 空の一括更新を受け付けない: 同上（`空の一括更新は受け付けない`）
- 未認証は一括更新できない: 同上（`未認証は一括更新できない`）
- 絞り込みのデバウンス・クエリストリングでの保持: 自動テストなし。目視確認で担保する
- 個別・一括の確認モーダルとトースト表示: 自動テストなし。目視確認で担保する
