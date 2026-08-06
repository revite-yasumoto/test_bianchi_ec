# 単位06: 管理画面 - 在庫管理

依存: 単位05

在庫マスタの一覧・個別更新・表示中の一括更新。

## スコープ

- 在庫一覧: バリエーション（SKU）単位の行。絞り込み（SKU区分・カテゴリ・商品名）・クリアボタン・件数表示
- 在庫の個別更新（確認モーダル付き）
- 表示中の件数を対象とした一括更新（確認モーダル付き）
- 在庫「あり／在庫切れ（0）」が判別できる表示

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Action） | `laravel-set:laravel` |
| クエリビルダ・トランザクション・一括更新 | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/stocks` | `admin.stocks.index` | 在庫一覧 |
| PUT | `/admin/stocks/{stock}` | `admin.stocks.update` | 個別更新 |
| PUT | `/admin/stocks/bulk` | `admin.stocks.bulk-update` | 表示中の一括更新 |

`bulk` を `{stock}` より先に定義し、ルートの衝突を避ける。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/StockController.php` | `index()` / `update()` / `bulkUpdate()` |
| FormRequest | `app/Http/Requests/Admin/Stock/UpdateStockRequest.php` | 在庫数の必須・範囲 |
| FormRequest | `app/Http/Requests/Admin/Stock/BulkUpdateStockRequest.php` | `stocks` 配列（id と quantity の対）のバリデーション |
| Action | `app/Actions/Admin/Stock/BuildStockFilter.php` | 在庫一覧の絞り込みクエリの組み立て |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Stock/Index.tsx` | 絞り込みバー（＋「表示中を一括更新」ボタン）＋在庫一覧テーブル |
| Component | `resources/js/admin/Components/Stock/StockRow.tsx` | 現在庫バッジ・在庫数入力欄・個別更新ボタン |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「在庫」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
type StockRow = {
  stock_id: number;
  product_name: string;
  category_name: string;
  variant_label: string;      // 'アクア / M' または '規格なし'
  sku_code: string;           // SKUなしは product_code
  has_sku: boolean;
  quantity: number;
};
type Filters = {
  has_sku: 'all' | 'with' | 'without';
  category_id: number | null;
  q: string | null;           // 商品名の部分一致
};
type Props = {
  stocks: Paginated<StockRow>;
  categories: { id: number; name: string }[];
  filters: Filters;
  totalCount: number;         // 絞り込み前の全バリエーション件数
};
```

### バリデーション

`UpdateStockRequest`:

| 項目 | ルール |
|---|---|
| `quantity` | 必須・整数・0以上・999,999以下 |

`BulkUpdateStockRequest`:

| 項目 | ルール |
|---|---|
| `stocks` | 必須・配列・最小1要素・最大200要素 |
| `stocks.*.id` | 必須・`stocks.id` に存在 |
| `stocks.*.quantity` | 必須・整数・0以上・999,999以下 |

### 主要な処理フロー

**在庫一覧**:
1. `stocks` を起点に `variant.product.category` を eager load する（N+1 を作らない）
2. 絞り込みを適用する
   - `has_sku`: `products.has_sku` と一致（`all` は条件なし）
   - `category_id`: `products.category_id` と完全一致
   - `q`: `products.name` の部分一致
3. 並び順は商品名 → カラー → サイズ。ページネーション（1ページ100件）
4. 件数表示は「絞り込み後の件数 / 全N件」
5. `variant_label` は `color_name` と `size_name` を ` / ` で連結する。どちらも `null` なら `規格なし`

**個別更新**:
1. 入力欄を変更 → 「更新」ボタン → 確認モーダル（「〈商品名〉（〈バリエーション〉）の在庫数を更新します。この操作は取り消せません。」）
2. `stocks.quantity` を更新 → 一覧へリダイレクトし「在庫を更新しました」をフラッシュ

**一括更新**:
1. 「表示中を一括更新」ボタン → 確認モーダル（「現在表示されている N 件の在庫数を入力値で更新します。この操作は取り消せません。」）
2. 画面に表示中の全行の `id` と `quantity` を送信する
3. 1トランザクション内で `Stock` を `id` ごとに更新する。**送信されたバリエーションが表示中の絞り込み結果に含まれることをサーバー側で再検証する**（クライアントから任意の `id` を送られても、意図しない在庫が書き換わらないようにする）
4. 「N件の在庫を更新しました」をフラッシュ

**一括更新の再検証**: リクエストの絞り込み条件（`has_sku` / `category_id` / `q` / ページ番号）も併せて送信させ、サーバー側で同じ条件のクエリを再実行して得た `id` 集合と、送信された `id` 集合の差分を検証する。差分があれば更新せずエラーを返す。

## 業務ルール

- 在庫はフロントでは「在庫あり／在庫切れ」の二値でのみ表示する（在庫数は公開しない）。管理画面では実数を表示する
- 在庫数の変更履歴は保持しない（要件に記載なし）
- 在庫の減算は注文確定時（単位16）、加算は注文キャンセル時（単位07・17）に自動で行う。本画面は手動調整のための画面である

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Stock/StockIndexTest.php` | SKUあり商品が組み合わせ数の行に展開されること／SKUなし商品が「規格なし」1行になること／絞り込みの各条件／件数表示／N+1が発生しないこと |
| `tests/Feature/Admin/Stock/StockUpdateTest.php` | 在庫数が更新されること／負数・上限超過・非数値を弾くこと |
| `tests/Feature/Admin/Stock/StockBulkUpdateTest.php` | 表示中の全行が更新されること／絞り込み結果に含まれない `id` を送ると更新されずエラーになること／1件でも失敗すれば全件ロールバックされること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **一括更新の対象範囲**: 要件は「表示中の件数を対象とした一括更新」。ページネーションを入れる場合、「表示中」＝現在のページの行とする。確認モーダルの件数表示も現在ページの件数にする。
2. **一括更新の不正な `id` 送信への対処**: 要件に記載はないが、意図しない在庫の書き換えを防ぐため推奨=**サーバー側で絞り込み結果との一致を再検証する**。
3. **在庫変更履歴**: 実装しない（要件に記載なし）。
4. **在庫の予約（カート投入時の確保）**: 実装しない。在庫の減算は注文確定時のみ。同時注文で在庫が不足した場合の扱いは単位16で決める。
5. **ページネーション**: 要件に記載はないが、バリエーション数の増加に備えて推奨=**1ページ100件**。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で `docs/admin/stock.md` を作成する
- 本計画ファイルを削除し、トラッカーの状態を更新する
