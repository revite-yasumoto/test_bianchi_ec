# フロント 商品一覧

## 機能概要

- **対象画面・機能の目的:** 公開中の商品を一覧し、カテゴリ・価格帯・在庫状態で絞り込み、並び替えて商品詳細へ遷移させる。
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
| 24件の商品 / 全58件（絞り込み時のみ）          [ 新着順        v ] |
| カテゴリ: (すべて) (ロードバイク) (MTB) (シティ) (eバイク) (アパレル)  |
| 価格帯: (すべて) (〜1万円) (1万〜5万円) (5万〜15万円) (15万円〜)      |
| 在庫: (すべて) (在庫あり) (在庫切れ)                                 |
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

- 絞り込みはカテゴリ・価格帯・在庫の3軸をチップで並べ、各行の先頭に軸名（カテゴリ／価格帯／在庫）を添える。選択中のチップは背景をブランド色で塗る。
- 並び替えのプルダウンは件数表示と同じ行の右端に置く。SP幅では件数とプルダウンが左右に並ぶ。
- カードは `minmax(200px,1fr)` の自動折り返しグリッドで、画面幅に応じて列数が変わる（SP幅では1〜2列）。
- カード内のカテゴリ名は1行、商品名は2行までとし、超える分は末尾を「…」で省略する。商品名は1行のときも2行分の高さを確保し、並べたカードの高さを揃える。
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
    priceRanges: { value: string; label: string }[];
    stockFilters: { value: string; label: string }[];
    sorts: { value: string; label: string }[];
    filters: {
        category_id: number | null;
        price_range: string | null;
        stock: string | null;
        sort: string;
    };
    totalCount: number;
};
```

価格帯の区分は `App\Enums\PriceRange`、在庫状態は `App\Enums\StockFilter`、並び替えは `App\Enums\ProductSort` が単一情報源で、フロントの値は `resources/js/shared/lib/enums.ts` に同じ文字列で持つ。価格帯の境界は「下限以上・上限未満」で、上限のない区分は下限以上のみを見る。

| 区分 | 値 | ラベル |
|---|---|---|
| 在庫状態 | `in_stock` | 在庫あり |
| 在庫状態 | `sold_out` | 在庫切れ |
| 並び替え | `newest` | 新着順（既定） |
| 並び替え | `price_asc` | 価格が安い順 |
| 並び替え | `price_desc` | 価格が高い順 |

在庫状態の「すべて」は区分値を持たず、`stock` を付けないことで表す。`filters.sort` は既定を解決済みの値で、指定が無いときも `newest` が入る。

- `main_image_url` は `asset('storage/'.$path)` で生成した公開URL。画像未登録は `null`。`Storage::disk('public')->url()` は `APP_URL` を基準にした固定値を返すため使わない。ポートやホストが環境ごとに違っても解決できるよう、リクエストを基準にする。画像URLを組み立てる箇所はすべてこの方法に揃える。
- `ASSET_URL`（`config('app.asset_url')`）を設定した環境では、`asset()` がそちらを基準にする。CDN等から配信する場合に使う。未設定のときが上記のリクエスト基準。
- `is_sold_out` の判定は [docs/front/product-show.md](product-show.md)「在庫の二値判定」が正本。
- `totalCount` は公開商品の総件数。`products.total` は絞り込み後の件数。

### 入力値バリデーションルール

クエリパラメータは `category_id`・`price_range`・`stock`・`sort`。存在しないカテゴリの値が来た場合は該当0件になる（エラーにはしない）。`price_range` が `PriceRange` に、`stock` が `StockFilter` に無い値のときは絞り込みを適用しない（誤ったURLで0件になるより、全件を出すほうが利用者の意図に近い）。`sort` が `ProductSort` に無い値のときは既定の新着順で並べる。

### 主要な処理フロー

1. `is_published = true` の商品を対象にする。
2. `BuildProductCard` が `category` と `mainImage` の eager load と、取扱対象かつ在庫が1以上のバリエーション数のサブクエリ（`withCount`）を付与する。商品件数が増えてもクエリ数は変わらない。同じ組み立てをTOPのランキング・おすすめ・閲覧履歴でも使う。
3. `category_id` の指定があれば絞り込む。
4. `price_range` の指定があれば `products.price` で絞り込む。
5. `stock` の指定があれば在庫状態で絞り込む。「在庫あり」は在庫ありのバリエーションを持つ商品、「在庫切れ」は1件も持たない商品を残す。条件は `BuildProductCard` がカードの `is_sold_out` に使うものと同じサブクエリで、カードのバッジと絞り込みの結果が食い違わない。
6. 3〜5の絞り込みを併用した場合は、すべてを満たす商品だけが残る。
7. `sort` の区分に従って並べる。新着順は `id` の降順。価格順は `products.price` の昇順・降順とし、同価格の並びが実行ごとに変わらないよう `id` の降順を続けて指定する。
8. 1ページ24件のページネーションを行い、クエリ文字列を保持する。
9. カテゴリの選択肢は `sort_order` 昇順・`id` 昇順で返す。

## 業務ルール

- 絞り込みチップ・並び替えの選択はいずれも1ページ目から表示し直す（並び順が変わると同じページ番号でも中身が入れ替わるため）。他の軸の選択はそのまま引き継ぐ。
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
| Component | `resources/js/front/Components/Product/FilterChips.tsx` |
| Component | `resources/js/front/Components/Product/SortSelect.tsx` |
| Enum | `app/Enums/PriceRange.php` |
| Enum | `app/Enums/StockFilter.php` |
| Enum | `app/Enums/ProductSort.php` |
| ロジック | `resources/js/front/lib/productIndexQuery.ts` |
| 型定義 | `resources/js/shared/lib/enums.ts` |
| Test | `tests/Feature/Front/Product/ProductIndexTest.php` |
| Test | `tests/Feature/Front/Product/ProductStockDisplayTest.php` |

## 受け入れ条件

- 未ログインでも公開商品の一覧が表示され、カテゴリ名・価格・在庫状態が渡る: `tests/Feature/Front/Product/ProductIndexTest.php`（`未ログインでも公開商品の一覧が表示される`）
- 非公開商品が一覧に出ない: 同上（`非公開商品は一覧に表示されない`）
- カテゴリで絞り込め、`totalCount` は絞り込み前の件数になる: 同上（`カテゴリで絞り込める`）
- 価格帯で絞り込め、境界値が下限以上・上限未満で判定される: 同上（`価格帯で絞り込める`・`価格帯の境界は下限を含み上限を含まない`）
- 上限のない価格帯は下限以上のみで絞り込む: 同上（`上限のない価格帯は下限以上の商品が残る`）
- カテゴリと価格帯を併用すると両方を満たす商品だけが残る: 同上（`カテゴリと価格帯を併用できる`）
- 不正な価格帯の値では絞り込まれない: 同上（`存在しない価格帯の値は絞り込みに使われない`）
- 在庫状態で絞り込め、カードの在庫切れバッジと同じ判定で振り分けられる: 同上（`在庫ありで絞り込める`・`在庫切れで絞り込める`）
- 在庫状態をカテゴリ・価格帯と併用すると、すべてを満たす商品だけが残る: 同上（`在庫状態はカテゴリ・価格帯と併用できる`）
- 不正な在庫状態の値では絞り込まれない: 同上（`存在しない在庫状態の値は絞り込みに使われない`）
- 価格の昇順・降順で並べ替えられる: 同上（`価格が安い順で並べ替えられる`・`価格が高い順で並べ替えられる`）
- 並び替えの指定が無いときと不正な値のときは新着順で並ぶ: 同上（`並び替えの指定が無いときは新着順で並ぶ`・`存在しない並び替えの値は新着順で扱われる`）
- 並び替えと絞り込みを併用でき、絞り込み後の商品が指定の順で並ぶ: 同上（`並び替えは絞り込みと併用できる`）
- 在庫0の商品に `is_sold_out` が立つ: 同上（`在庫が0の商品は在庫切れとして返る`）
- カテゴリの選択肢が並び順で返る: 同上（`カテゴリの選択肢が並び順で返る`）
- 商品件数が増えてもクエリ数が増えない（N+1が発生しない）: 同上（`商品件数が増えてもクエリ数が増えない`）
- 画像URLがアクセス元のホスト・ポートに追従する: 同上（`商品画像のリンク先はアクセス元のホストとポートに追従する`）
- 一覧の在庫切れ判定が詳細と一致する: `tests/Feature/Front/Product/ProductStockDisplayTest.php`
- カードのグリッド折り返し・在庫切れバッジ・プレースホルダー表示: 自動テストなし。目視確認で担保する
- 絞り込みチップ3行の並びと軸名の表示・選択中チップの配色・並び替えプルダウンの配置: 自動テストなし。目視確認で担保する
