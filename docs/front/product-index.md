# フロント 商品一覧

## 機能概要

- **対象画面・機能の目的:** 公開中の商品を一覧し、カテゴリで絞り込んで商品詳細へ遷移させる。
- **URL / メソッド:** `GET /products`（ルート名 `products.index`）
- **アクセス権限・ミドルウェア:** なし（未ログインでも閲覧できる）。購入導線（カート投入・お気に入り）でログインを求める。
- **本ドキュメントのスコープ:** 商品一覧画面と、一覧・TOP・お気に入りで共用する商品カード。`ProductController::index()` を扱い、`show()` は [docs/front/product-show.md](product-show.md) が扱う。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `products` | 一覧の対象（`is_published = true` のみ） |
| `categories` | カテゴリチップの選択肢・カード内のカテゴリ名 |
| `product_images` | カードのメイン画像（`sort_order = 0`） |
| `product_variants` | 在庫二値判定の対象（`is_available = true` のみ） |
| `stocks` | 在庫二値判定の在庫数 |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| HOME / PRODUCTS                                                   |
| 商品一覧                                                           |
| 24件の商品 / 全58件（絞り込み時のみ「/ 全N件」を付ける）              |
| (すべて) (ロードバイク) (MTB) (シティ) (eバイク) (パーツ) (アパレル)  |
|                                                                   |
| +-----------+ +-----------+ +-----------+ +-----------+           |
| |         [在庫切れ]|      | |           | |           |           |
| |  画像     | |  画像     | |  画像     | |  画像     |           |
| +-----------+ +-----------+ +-----------+ +-----------+           |
| | カテゴリ   | | カテゴリ  | | カテゴリ   | | カテゴリ  |           |
| | 商品名     | | 商品名    | | 商品名     | | 商品名    |           |
| | ¥398,000  | | ¥268,000 | | ¥198,000  | | ¥98,000  |           |
| +-----------+ +-----------+ +-----------+ +-----------+           |
|                                                                   |
|                    [前へ] [1] [2] [3] [次へ]                       |
+------------------------------------------------------------------+
```

- カードは `minmax(200px,1fr)` の自動折り返しグリッドで、画面幅に応じて列数が変わる（SP幅では1〜2列）。
- 画像が未登録の商品は、カテゴリごとの配色にカテゴリ相応のシルエットを重ね、左下に商品識別コードを添える（[docs/front/common-layout.md](common-layout.md) の `ProductVisual` が正本）。
- 在庫切れの商品はカード右上に「在庫切れ」バッジを重ねる。カード自体は押せる（詳細へ遷移できる）。
- 該当0件のときはグリッドの代わりに「該当する商品はありません」を表示する。
- ページ送りは1ページ以下のときに表示しない。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Components/Product/ProductCard.tsx
type ProductCardData = {
    id: number;
    name: string;
    category_name: string;
    product_code: string;
    price: number;
    main_image_url: string | null;
    is_sold_out: boolean;
};

// resources/js/front/Pages/Product/Index.tsx
type Props = {
    products: Paginated<ProductCardData>;
    categories: { id: number; name: string }[];
    filters: { category_id: number | null };
    totalCount: number;
};
```

- `main_image_url` は `asset('storage/'.$path)` で生成した公開URL。画像未登録は `null`。`Storage::disk('public')->url()` は `APP_URL` を基準にした固定値を返すため使わない。ポートやホストが環境ごとに違っても解決できるよう、リクエストを基準にする。画像URLを組み立てる箇所はすべてこの方法に揃える。
- `ASSET_URL`（`config('app.asset_url')`）を設定した環境では、`asset()` がそちらを基準にする。CDN等から配信する場合に使う。未設定のときが上記のリクエスト基準。
- `is_sold_out` の判定は [docs/front/product-show.md](product-show.md)「在庫の二値判定」が正本。
- `totalCount` は公開商品の総件数。`products.total` は絞り込み後の件数。

### 入力値バリデーションルール

クエリパラメータ `category_id` のみ。存在しないカテゴリの値が来た場合は該当0件になる（エラーにはしない）。

### 主要な処理フロー

1. `is_published = true` の商品を対象にする。
2. `BuildProductCard` が `category` と `mainImage` の eager load と、取扱対象かつ在庫が1以上のバリエーション数のサブクエリ（`withCount`）を付与する。商品件数が増えてもクエリ数は変わらない。同じ組み立てをTOPのランキング・おすすめ・閲覧履歴でも使う。
3. `category_id` の指定があれば絞り込む。
4. `id` の降順で1ページ24件のページネーションを行い、クエリ文字列を保持する。
5. カテゴリの選択肢は `sort_order` 昇順・`id` 昇順で返す。

## 業務ルール

- 並び替え（価格順・新着順）は要件に定義がないため実装しない。
- 非公開商品は一覧に出さない。詳細への直接アクセスの扱いは [docs/front/product-show.md](product-show.md) を参照。

## 関連ドキュメント

- [docs/front/product-show.md](product-show.md) — 商品詳細。在庫の二値判定・カート投入の正本
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout`・ページ送り・商品画像プレースホルダー（`ProductVisual`）の正本
- [docs/admin/product-index.md](../admin/product-index.md) — 商品マスタ（公開状態・カテゴリ・価格の登録元）
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/ProductController.php` |
| Action | `app/Actions/Front/Product/BuildProductCard.php` |
| Page | `resources/js/front/Pages/Product/Index.tsx` |
| Component | `resources/js/front/Components/Product/ProductCard.tsx` |
| Component | `resources/js/front/Components/Product/CategoryChips.tsx` |
| Test | `tests/Feature/Front/Product/ProductIndexTest.php` |
| Test | `tests/Feature/Front/Product/ProductStockDisplayTest.php` |

## 受け入れ条件

- 未ログインでも公開商品の一覧が表示され、カテゴリ名・価格・在庫状態が渡る: `tests/Feature/Front/Product/ProductIndexTest.php`（`未ログインでも公開商品の一覧が表示される`）
- 非公開商品が一覧に出ない: 同上（`非公開商品は一覧に表示されない`）
- カテゴリで絞り込め、`totalCount` は絞り込み前の件数になる: 同上（`カテゴリで絞り込める`）
- 在庫0の商品に `is_sold_out` が立つ: 同上（`在庫が0の商品は在庫切れとして返る`）
- カテゴリの選択肢が並び順で返る: 同上（`カテゴリの選択肢が並び順で返る`）
- 商品件数が増えてもクエリ数が増えない（N+1が発生しない）: 同上（`商品件数が増えてもクエリ数が増えない`）
- 画像URLがアクセス元のホスト・ポートに追従する: 同上（`商品画像のリンク先はアクセス元のホストとポートに追従する`）
- 一覧の在庫切れ判定が詳細と一致する: `tests/Feature/Front/Product/ProductStockDisplayTest.php`
- カードのグリッド折り返し・在庫切れバッジ・プレースホルダー表示: 自動テストなし。目視確認で担保する
