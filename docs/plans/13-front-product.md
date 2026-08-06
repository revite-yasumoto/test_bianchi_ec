# 単位13: フロント - 商品一覧・商品詳細

依存: 単位02, 単位05, 単位06

フロントの商品閲覧の中核。SKUの有無による表示の出し分け、在庫の二値表示、お気に入り、閲覧履歴の記録を含む。

## スコープ

- 商品一覧: カテゴリ絞り込み・件数表示・商品カード・在庫切れバッジ
- 商品詳細: 画像（メイン＋サムネイル最大10枚）・価格・在庫二値表示・SKU選択・カートに入れる・お気に入り・商品説明／スペックのタブ・「送料・お支払い方法・発送日数について」モーダル・「この商品について問い合わせる」
- お気に入りの登録／解除
- 閲覧履歴の記録
- カートへの投入（カートページ本体は単位15）

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Action） | `laravel-set:laravel` |
| クエリビルダ・eager load | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php`）

| メソッド | パス | ルート名 | 認証 | 内容 |
|---|---|---|---|---|
| GET | `/products` | `products.index` | 不要 | 商品一覧 |
| GET | `/products/{product}` | `products.show` | 不要 | 商品詳細 |
| POST | `/cart/items` | `cart.items.store` | 必要 | カートに追加 |
| POST | `/favorites` | `favorites.store` | 必要 | お気に入り登録 |
| DELETE | `/favorites/{product}` | `favorites.destroy` | 必要 | お気に入り解除 |

商品一覧・詳細は未ログインでも閲覧できる。カート投入・お気に入りはログインが必要（未ログイン時はログイン画面へ遷移し、ログイン後に元の商品詳細へ復帰する）。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/ProductController.php` | `index()` / `show()` |
| Controller | `app/Http/Controllers/Front/CartItemController.php` | `store()`（単位15で `update()` / `destroy()` を追加） |
| Controller | `app/Http/Controllers/Front/FavoriteController.php` | `store()` / `destroy()` |
| FormRequest | `app/Http/Requests/Front/Cart/StoreCartItemRequest.php` | バリエーションと数量の検証 |
| Action | `app/Actions/Front/Product/RecordBrowsingHistory.php` | 閲覧履歴を upsert し、直近6件を超える分を削除 |
| Action | `app/Actions/Front/Product/BuildProductDetail.php` | 商品詳細の Props を組み立て（SKUの在庫状況を含む） |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/front/Pages/Product/Index.tsx` | パンくず・見出し・件数・カテゴリチップ・商品カードグリッド |
| Page | `resources/js/front/Pages/Product/Show.tsx` | 左に画像、右に商品情報＋購入導線の2カラム。下部にタブ |
| Component | `resources/js/front/Components/Product/ProductCard.tsx` | 画像・在庫切れバッジ・カテゴリ・商品名・価格（TOP・一覧・お気に入りで共用） |
| Component | `resources/js/front/Components/Product/CategoryChips.tsx` | カテゴリ絞り込みチップ |
| Component | `resources/js/front/Components/Product/ImageGallery.tsx` | メイン画像＋サムネイル5列グリッド。サムネイルのクリックでメインを切り替える |
| Component | `resources/js/front/Components/Product/VariantSelector.tsx` | カラー・サイズの選択。売り切れ／取扱対象外は取り消し線＋グレーアウトで選択不可 |
| Component | `resources/js/front/Components/Product/StockBadge.tsx` | 「在庫あり」／「在庫切れ」バッジ |
| Component | `resources/js/front/Components/Product/DetailTabs.tsx` | 商品説明／スペックのタブ切り替え |
| Component | `resources/js/front/Components/Product/ShippingInfoModal.tsx` | 送料・支払方法・発送日数の案内モーダル（47都道府県の送料・日数表を含む） |
| Component | `resources/js/front/Components/Product/FavoriteButton.tsx` | お気に入りの登録／解除トグル |
| Hook | `resources/js/front/hooks/useVariantSelection.ts` | 選択中のカラー・サイズから購入可能なバリエーションを解決する |
| Component | `resources/js/front/Components/CartDrawer.tsx` | **修正**: カート投入後に開き、実データを表示する |

## インターフェース ＆ データロジック

### Props の型

```ts
// Product/Index.tsx
type ProductCardData = {
  id: number;
  name: string;
  category_name: string;
  product_code: string;
  price: number;
  main_image_url: string | null;
  is_sold_out: boolean;         // 全バリエーションの在庫が0
};
type Props = {
  products: Paginated<ProductCardData>;
  categories: { id: number; name: string }[];
  filters: { category_id: number | null };
  totalCount: number;
};

// Product/Show.tsx
type VariantData = {
  id: number;
  size_name: string | null;
  color_name: string | null;
  sku_code: string | null;
  is_available: boolean;        // 取扱対象（規格なしでない）
  in_stock: boolean;            // 在庫 > 0
};
type Props = {
  product: {
    id: number;
    product_code: string;
    name: string;
    category_name: string;
    price: number;
    description: string | null;
    has_sku: boolean;
    is_sold_out: boolean;
    images: { url: string; sort_order: number }[];
    specs: { label: string; value: string }[];
    variants: VariantData[];
    sizes: string[];             // 選択肢の並び順（規格管理の sort_order 順）
    colors: string[];
  };
  isFavorited: boolean;
  shippingTable: { prefecture_name: string; fee: number; delivery_days: number }[];
  ecSetting: { free_shipping_threshold: number; cod_fee: number };
};
```

### バリデーション

`StoreCartItemRequest`:

| 項目 | ルール |
|---|---|
| `product_variant_id` | 必須・`product_variants.id` に存在・**その商品が公開中**・**`is_available = true`**・**在庫が1以上** |
| `quantity` | 必須・整数・1以上・99以下・**在庫数以下** |

在庫・公開状態・取扱可否はサーバー側で必ず再検証する（クライアントの表示制御だけに依存しない）。

### 在庫の二値判定

| 対象 | 「在庫あり」の条件 |
|---|---|
| 商品（一覧・詳細の上部バッジ） | その商品の `is_available = true` のバリエーションのうち、`stocks.quantity > 0` が1件以上存在する |
| バリエーション（カラー／サイズの選択肢） | そのバリエーションの `stocks.quantity > 0` |

フロントには在庫数を渡さない（`in_stock: boolean` のみを渡す）。

### カラー／サイズの選択不可判定（`VariantSelector`）

モックのロジックに合わせる。

| 選択肢 | 選択不可（取り消し線＋グレーアウト）の条件 |
|---|---|
| カラー | そのカラーの全サイズについて、取扱対象外 **または** 在庫0 |
| サイズ（カラー未選択時） | そのサイズの全カラーについて、取扱対象外 **または** 在庫0 |
| サイズ（カラー選択済み時） | 選択中のカラー × そのサイズが、取扱対象外 **または** 在庫0 |

カラーを選択し直したとき、選択中のサイズが選択不可になる場合はサイズの選択を解除する。

### 主要な処理フロー

**商品一覧**:
1. `products` のうち `is_published = true` のものを取得する
2. `category` と `mainImage` を eager load し、バリエーションの在庫合計をサブクエリで付与する（N+1 を作らない）
3. `category_id` が指定されていれば絞り込む
4. `id` の降順でページネーション（1ページ24件）
5. 件数表示は「N件の商品」

**商品詳細**:
1. `is_published = true` でなければ 404 を返す
2. `category` / `images`（`sort_order` 昇順）/ `specs`（`sort_order` 昇順）/ `variants.stock` を eager load する
3. カラー・サイズの選択肢の並び順は `spec_options.sort_order` に従う。`spec_options` に存在しない値は末尾に回す
4. ログイン中なら `RecordBrowsingHistory` で閲覧履歴を記録する（`viewed_at` を更新し、直近6件を超える分を削除）
5. ログイン中なら `isFavorited` を判定する

**カートに追加**:
1. 未ログインならログイン画面へ（ログイン後に元の商品詳細へ復帰）
2. SKUあり商品でカラー・サイズが未選択なら、フロント側で「カラーとサイズを選択してください」を表示し送信しない
3. サーバー側でバリエーションの公開状態・取扱可否・在庫を再検証する
4. `cart_items` を `(user_id, product_variant_id)` で upsert する。既存があれば数量を加算する（上限99、かつ在庫数以下）
5. カートドロワーを開き、「カートに追加しました」をフラッシュ

**お気に入り**: `favorites` を `(user_id, product_id)` で作成／削除する。未ログインならログイン画面へ。

**この商品について問い合わせる**: `/contact?product_name={商品名}` へ遷移する。お問い合わせ画面の「対象商品」に自動入力される（単位18）。

## 業務ルール

- 在庫表示は「在庫あり／在庫切れ」の二値のみ。在庫数はフロントに渡さない
- 在庫切れ商品は購入不可。「カートに入れる」ボタンを「在庫切れ」に切り替え、押下できない状態にする
- 「カートに入れる」は購入導線のため、他のボタンと配色を差別化する（`coral`）。お気に入りはアウトライン
- 非公開商品は一覧に出さず、詳細への直接アクセスは 404 とする
- 商品画像が0枚の商品は、プレースホルダー（カテゴリごとのグラデーション背景）を表示する
- 閲覧履歴はログイン中の会員のみ記録する（未ログイン時は記録しない）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/Product/ProductIndexTest.php` | 公開商品のみ表示されること／カテゴリ絞り込み／件数表示／在庫切れ商品に `is_sold_out` が立つこと／未ログインでも閲覧できること／N+1が発生しないこと |
| `tests/Feature/Front/Product/ProductShowTest.php` | 非公開商品が404になること／画像・スペックが `sort_order` 順で返ること／SKUあり商品でカラー・サイズの選択肢が返ること／在庫数がPropsに含まれないこと |
| `tests/Feature/Front/Product/ProductStockDisplayTest.php` | 全バリエーション在庫0で「在庫切れ」になること／1件でも在庫があれば「在庫あり」になること／取扱対象外のバリエーションは在庫判定から除外されること |
| `tests/Feature/Front/Product/BrowsingHistoryTest.php` | ログイン中に詳細を開くと履歴が記録されること／同一商品を再訪すると `viewed_at` が更新され行が増えないこと／7件目を見ると最古の1件が削除されること／未ログインでは記録されないこと |
| `tests/Feature/Front/Cart/AddToCartTest.php` | 正常追加／未ログインでログイン画面へリダイレクトされること／在庫0のバリエーションを弾くこと／取扱対象外のバリエーションを弾くこと／非公開商品のバリエーションを弾くこと／在庫数を超える数量を弾くこと／既存カート行の数量が加算されること |
| `tests/Feature/Front/Favorite/FavoriteTest.php` | 登録／解除／未ログインでログイン画面へリダイレクトされること／同一商品の二重登録が行を増やさないこと |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **商品一覧・詳細の閲覧に会員登録が必要か**: 要件は「ゲスト購入不可、会員登録・ログイン必須」と定めているが、これは購入についての制約と読める。推奨=**商品一覧・詳細・TOPは未ログインでも閲覧でき、カート投入以降でログインを求める**（モックも未ログイン状態からTOP・一覧・詳細を閲覧できる構成）。全ページで認証を必須にする場合は実行時に指示すること。
2. **商品画像のプレースホルダー**: モックは実画像を持たずグラデーション背景で代替している。推奨=**画像未登録時はカテゴリごとのグラデーションを表示する**。
3. **サムネイルの枚数**: モックは常に10枠を表示する。推奨=**登録済みの画像枚数分だけ表示する**（空枠を出さない）。
4. **カート投入時の数量指定**: モックは常に1個ずつ追加する。推奨=**モックどおり1個ずつ**（詳細画面に数量入力を置かない。数量変更はカートページで行う）。
5. **お気に入りの未ログイン時の挙動**: 推奨=ログイン画面へ遷移し、ログイン後に元の商品詳細へ復帰する。
6. **閲覧履歴の保持件数**: モックは6件。推奨=**直近6件**。
7. **ページネーション**: 要件に記載なし。推奨=1ページ24件。
8. **並び替え（価格順・新着順）**: 要件に記載なし。実装しない。
9. **在庫数を超える数量の扱い**: 推奨=バリデーションエラーとして「在庫が不足しています」を返す。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/front/product-index.md`
  - `docs/front/product-show.md`（在庫二値判定・SKU選択不可判定の**正本**。単位14・15・16の仕様書からリンクする）
  - `docs/front/favorite.md`
- 本計画ファイルを削除し、トラッカーの状態を更新する
