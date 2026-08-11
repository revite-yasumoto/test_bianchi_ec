# 商品一覧

## 機能概要

- **対象画面・機能の目的:** 登録済み商品の一覧・絞り込みと、登録／編集画面への導線。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/products` | `admin.products.index` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 一覧の表示・絞り込み・ページネーション。登録／編集／削除は [docs/admin/product-form.md](product-form.md) が正本。

## 使用テーブル

`products` を主体に `categories`（カテゴリ名）・`product_variants` / `stocks`（在庫合計）を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

在庫合計は `Product::stocks()`（`product_variants` を経由する `hasManyThrough`）に対する `withSum()` で1クエリにまとめる。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| 商品名・商品ID | カテゴリ | SKU有無 | 価格帯    |[クリア] 8件/全24件 |
+------------------------------------------------------------------+
| 商品ID   | 商品名        | カテゴリ | 価格   |在庫  |SKU|公開 |    |
|----------|---------------|----------|--------|------|---|-----|----|
| RC7-105  | ロードスター  | ロード   |¥398,000|在庫12|あり|公開 |編集|
| MT3-STD  | トレイルヘッド| MTB      |¥198,000|在庫切れ|なし|非公開|編集|
+------------------------------------------------------------------+
                         [前へ] [1] [2] [次へ]
```

- 絞り込みは入力から400ms後に反映する（入力のたびにリクエストを送らない）。条件はクエリストリングに保持され、編集画面から戻ったときも維持される。
- 「クリア」ボタンで全条件を初期化する。
- 件数表示は「絞り込み後の件数 / 全N件」。
- 該当0件のときは「該当する商品がありません」を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type ProductRow = {
    id: number;
    product_code: string;
    name: string;
    category_name: string;
    price: number;
    total_stock: number;   // 全バリエーションの在庫合計
    has_sku: boolean;
    is_published: boolean;
};

type Filters = {
    q: string | null;
    category_id: number | null;
    has_sku: string;        // 'all' | 'with' | 'without'
    price_min: number | null;
    price_max: number | null;
};

type Props = {
    products: Paginated<ProductRow>;
    categories: { id: number; name: string }[];
    filters: Filters;
    totalCount: number;     // 絞り込み前の全件数
};
```

- **入力値バリデーションルール:** 絞り込みはフォーム送信ではないため FormRequest を使わない。`has_sku` は `all` / `with` / `without` の許可リストと照合し、外れた値は `all` に丸める。数値項目は整数へキャストする。

- **主要な処理フロー（`index()` と `BuildProductFilter`）:**

1. `category` を eager load し、`withSum('stocks', 'quantity')` で在庫合計を付与する。
2. 絞り込みを適用する。
   - `q`: `name` の部分一致 **または** `product_code` の部分一致。`%` と `_` はエスケープする。
   - `category_id`: 完全一致。
   - `has_sku`: `with` / `without` を真偽値に変換して一致。`all` は条件なし。
   - `price_min` / `price_max`: 範囲指定。`price` は整数列のため比較値も整数で渡す。
3. `id` の降順で1ページ50件のページネーションを行い、クエリストリングを引き継ぐ。

## 業務ルール

- 在庫0の商品も「公開」のまま一覧に残る。在庫の状態はバッジ（在庫◯／在庫切れ）で表し、公開状態は自動で変えない。
- 一覧に削除ボタンは置かない。削除は編集画面から行う（[docs/admin/product-form.md](product-form.md) 参照）。

## 関連ドキュメント

- [docs/admin/product-form.md](product-form.md) — 商品登録／編集／削除の正本
- [docs/admin/common-layout.md](common-layout.md) — `FilterBar`・`DataTable`・`Pagination` の正本
- [docs/admin/category.md](category.md) — 絞り込みの選択肢になるカテゴリの管理
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/ProductController.php` |
| Action | `app/Actions/Admin/Product/BuildProductFilter.php` |
| Model | `app/Models/Product.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Product/Index.tsx` |
| Test | `tests/Feature/Admin/Product/ProductIndexTest.php` |

## 受け入れ条件

- 一覧が表示され在庫合計が算出される: `tests/Feature/Admin/Product/ProductIndexTest.php`（`商品一覧が表示され在庫合計が算出される`）
- 商品名・商品IDで絞り込める: 同上（`商品名で絞り込める`・`商品識別コードでも絞り込める`）
- カテゴリで絞り込める: 同上（`カテゴリで絞り込める`）
- SKU有無で絞り込める: 同上（`規格の有無で絞り込める`）
- 価格帯で絞り込める: 同上（`価格帯で絞り込める`）
- 複数条件を組み合わせられる: 同上（`複数の条件を組み合わせて絞り込める`）
- 該当0件で空の一覧になる: 同上（`該当がない場合は空の一覧になる`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 絞り込みのデバウンス・クエリストリングでの保持: 自動テストなし。目視確認で担保する
- ページネーションの表示・遷移: 自動テストなし。目視確認で担保する
