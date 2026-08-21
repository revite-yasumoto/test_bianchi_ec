# フロント 商品詳細

本書はフロント全体における**在庫の二値判定**と**規格（カラー・サイズ）の選択不可判定**の正本。TOP・カート・購入手続きの仕様書はこれらを再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** 商品の画像・価格・在庫・規格を提示し、カート投入・お気に入り登録・問い合わせへ導く。
- **URL / メソッド:**
  - `GET /products/{product}`（ルート名 `products.show`）
  - `POST /cart/items`（ルート名 `cart.items.store`）
- **アクセス権限・ミドルウェア:** 詳細の閲覧は認証不要。`POST /cart/items` は `auth` ミドルウェアで保護し、未ログインの投入はログイン画面へ遷移させる（ログイン後は投入元の商品詳細へ戻る）。
- **本ドキュメントのスコープ:** 商品詳細画面、カートへの投入、閲覧履歴の記録。カート画面本体は [docs/front/cart.md](cart.md)、お気に入りは [docs/front/favorite.md](favorite.md) が扱う。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `products` | 商品の基本情報。`is_published = false` は404 |
| `categories` | カテゴリ名 |
| `product_images` | 画像ギャラリー（`sort_order` 昇順） |
| `product_specs` | スペックタブ（`sort_order` 昇順） |
| `product_variants` | 規格の選択肢・取扱可否 |
| `stocks` | 在庫の二値判定 |
| `spec_options` | カラー・サイズの並び順 |
| `cart_items` | カート投入先（`user_id` + `product_variant_id` で一意） |
| `favorites` | お気に入り済み判定 |
| `browsing_histories` | 閲覧履歴（ログイン会員のみ） |
| `shipping_settings` / `prefectures` | 送料・発送日数の案内モーダル |
| `ec_settings` | 送料無料しきい値・代引き手数料 |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| ← 商品一覧へ戻る                                                   |
| +---------------------+  カテゴリ                                  |
| |                     |  商品名（h1）                               |
| |     メイン画像        |  商品ID RC7-105                            |
| |                     |  ¥398,000 税込                             |
| +---------------------+  [在庫あり]                                 |
| [t][t][t][t][t]          カラー  (ブラック)(レッド)(~~ホワイト~~)      |
| （サムネイル5列）          サイズ  (S)(M)(~~L~~)                      |
|                          SKU: RC7-105-11                          |
|                          ※カラーとサイズを選択してください（警告時）    |
|                          [  カートに入れる  ] [ ♡ お気に入り ]        |
|                          送料・お支払い方法・発送日数について →         |
|                          この商品について問い合わせる →                |
+------------------------------------------------------------------+
| [商品説明] [スペック]                                                |
| 商品説明本文 ／ スペックの定義リスト                                   |
+------------------------------------------------------------------+
```

- 2カラムは `lg`（1024px）以上で横並び、未満では縦積みになる。
- 選択不可の規格はグレーアウト（文字色を淡色化し、背景を沈めた色にする）で表示し、押せない。取り消し線は使わない（在庫切れを示す表現を画面内で1種類に揃える）。
- 在庫切れの商品は「カートに入れる」を「在庫切れ」に変え、灰色で押せない状態にする。
- 画像が未登録の商品は、メイン画像の枠にカテゴリ相応のシルエットと商品識別コードを出す（[docs/front/common-layout.md](common-layout.md) の `ProductVisual` が正本）。サムネイルは登録済みの枚数分のみ表示し、1枚以下のときは表示しない。
- 「送料・お支払い方法・発送日数について」は同一画面上のモーダルを開く（`Escape`・背景クリックで閉じる）。
- 「この商品について問い合わせる」は `/contact?product_id=<商品ID>` へ遷移する。問い合わせ側は商品IDから商品名を引き直す（[docs/front/contact.md](contact.md)）。
- 送料モーダルの末尾から買い物ガイド（`guide`）へ遷移できる（[docs/front/static-pages.md](static-pages.md)）。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/hooks/useVariantSelection.ts
type VariantData = {
    id: number;
    size_name: string | null;
    color_name: string | null;
    sku_code: string | null;
    is_available: boolean;   // 取扱対象（規格の組み合わせとして販売する）
    in_stock: boolean;       // 在庫が1以上
};

// resources/js/front/Components/Product/PurchasePanel.tsx
type ProductDetailData = {
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
    sizes: string[];   // spec_options.sort_order 順
    colors: string[];  // 同上
};

// resources/js/front/Pages/Product/Show.tsx
type Props = {
    product: ProductDetailData;
    isFavorited: boolean;
    shippingTable: { prefecture_name: string; fee: number; delivery_days: number }[];
    ecSetting: { free_shipping_threshold: number; cod_fee: number };
};
```

**在庫数はフロントへ渡さない。** バリエーションごとの `in_stock`（boolean）のみを渡す。

### 在庫の二値判定（正本）

| 対象 | 「在庫あり」の条件 |
|---|---|
| 商品（一覧のバッジ・詳細上部のバッジ） | `is_available = true` のバリエーションのうち、`stocks.quantity > 0` が1件以上存在する |
| バリエーション（カラー／サイズの選択肢） | そのバリエーションの `stocks.quantity > 0` |

在庫レコードが無いバリエーションは在庫0として扱う。取扱対象外（`is_available = false`）のバリエーションは、在庫があっても商品の在庫判定から除外する。

### 規格の選択不可判定（正本）

| 選択肢 | 選択不可（グレーアウト）の条件 |
|---|---|
| カラー | そのカラーの全サイズについて、取扱対象外 **または** 在庫0 |
| サイズ（カラー未選択時） | そのサイズの全カラーについて、取扱対象外 **または** 在庫0 |
| サイズ（カラー選択済み時） | 選択中のカラー × そのサイズが、取扱対象外 **または** 在庫0 |

カラーを選び直したときに、選択中のサイズがそのカラーで購入できなくなる場合はサイズの選択を解除する。カラーのみ／サイズのみを持つ商品では、存在しない軸を `null` 1件として同じ判定に載せる。

### 入力値バリデーションルール（`POST /cart/items`）

| 項目 | ルール |
|---|---|
| `product_variant_id` | 必須・整数・`product_variants.id` に存在。加えて、その商品が公開中かつ `is_available = true`（違反時「この商品は現在購入できません。」） |
| `quantity` | 必須・整数・1以上99以下。加えて、カート内の既存数量との合計が在庫数以下（違反時「在庫が不足しています。」）かつ99以下 |

公開状態・取扱可否・在庫は表示時点から変わりうるため、投入時にサーバー側で必ず再検証する（クライアントの表示制御だけに依存しない）。

### 主要な処理フロー

**商品詳細の表示**

1. `is_published = false` の商品は404を返す。
2. `category` / `images`（`sort_order` 昇順）/ `specs`（`sort_order` 昇順）/ `variants.stock` を eager load する。
3. カラー・サイズの選択肢は `spec_options.sort_order` 昇順に並べ、`spec_options` に無い値は末尾へ回す（規格管理から削除された値を持つ商品でも選択肢を落とさないため）。
4. ログイン中なら閲覧履歴を記録し、`isFavorited` を判定する。未ログインでは履歴を記録せず `isFavorited` は `false`。
5. 送料表（47都道府県）とEC基本設定（送料無料しきい値・代引き手数料）をモーダル用に渡す。

**閲覧履歴の記録**

1. `(user_id, product_id)` で `viewed_at` を更新または作成する（同一商品の再訪で行は増えない）。
2. `viewed_at` の降順で直近6件を残し、それを超えた履歴を削除する。

**カートへの投入**

1. 規格あり商品でカラー・サイズが未選択の場合、フロントで「カラーとサイズを選択してください」を表示し送信しない。
2. 未ログインの場合、`auth` ミドルウェアがログイン画面へ遷移させ、ログイン後は投入元の商品詳細へ戻る。
3. サーバー側でバリデーション（上記）を通す。
4. `(user_id, product_variant_id)` の行があれば数量を加算し、無ければ作成する。数量は常に1ずつ加算する（詳細画面に数量入力を置かない。数量の変更はカート画面で行う）。
5. 「カートに追加しました」をフラッシュし、カートドロワーを開く。

## 業務ルール

- 在庫表示は「在庫あり／在庫切れ」の二値のみとし、在庫数は画面にもPropsにも出さない。
- 閲覧履歴はログイン中の会員のみ記録する。未ログインの閲覧は記録しない。
- オンライン決済は実装対象外のため、支払い案内は銀行振込・代金引換のみを表示する。

## 関連ドキュメント

- [docs/front/product-index.md](product-index.md) — 商品一覧。カードの在庫切れ表示は本書の判定に従う
- [docs/front/favorite.md](favorite.md) — お気に入りの登録・解除の正本
- [docs/front/cart.md](cart.md) — カートページの正本。投入後の明細・数量変更・削除を扱う
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout`・カートドロワー・共通UIの正本
- [docs/front/contact.md](contact.md) — 「この商品について問い合わせる」の遷移先の正本
- [docs/front/static-pages.md](static-pages.md) — 送料モーダルから案内する買い物ガイドの正本
- [docs/shipping-calculation.md](../shipping-calculation.md) — 送料・配達予定日の算出の正本（本画面は案内表示のみで算出は行わない）
- [docs/admin/product-form.md](../admin/product-form.md) — 商品・規格・画像・スペックの登録元
- [docs/admin/stock.md](../admin/stock.md) — 在庫数の更新元
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/ProductController.php` |
| Controller | `app/Http/Controllers/Front/CartItemController.php` |
| FormRequest | `app/Http/Requests/Front/Cart/StoreCartItemRequest.php` |
| Action | `app/Actions/Front/Product/BuildProductDetail.php` |
| Action | `app/Actions/Front/Product/RecordBrowsingHistory.php` |
| Model | `app/Models/ProductVariant.php` |
| Page | `resources/js/front/Pages/Product/Show.tsx` |
| Component | `resources/js/front/Components/Product/PurchasePanel.tsx` |
| Component | `resources/js/front/Components/Product/ImageGallery.tsx` |
| Component | `resources/js/front/Components/Product/VariantSelector.tsx` |
| Component | `resources/js/front/Components/Product/StockBadge.tsx` |
| Component | `resources/js/front/Components/Product/DetailTabs.tsx` |
| Component | `resources/js/front/Components/Product/ShippingInfoModal.tsx` |
| Hook | `resources/js/front/hooks/useVariantSelection.ts` |
| Test | `tests/Feature/Front/Product/ProductShowTest.php` |
| Test | `tests/Feature/Front/Product/ProductStockDisplayTest.php` |
| Test | `tests/Feature/Front/Product/BrowsingHistoryTest.php` |
| Test | `tests/Feature/Front/Cart/AddToCartTest.php` |

## 受け入れ条件

- 未ログインでも公開商品の詳細が表示される: `tests/Feature/Front/Product/ProductShowTest.php`（`未ログインでも公開商品の詳細が表示される`）
- 非公開商品の詳細が404になる: 同上（`非公開商品の詳細は見つからない`）
- 画像・スペックが `sort_order` 順で返る: 同上（`画像とスペックが並び順で返る`）
- カラー・サイズが規格管理の並び順で返り、未登録の値は末尾に回る: 同上（`規格あり商品の選択肢が規格管理の並び順で返る`・`規格管理に無い選択肢は末尾に回る`）
- Propsに在庫数が含まれない: 同上（`バリエーションに在庫数は含まれない`）
- 送料表とEC基本設定が渡る: 同上（`送料表と基本設定が渡される`）
- 在庫の二値判定（全件0で在庫切れ／1件でも在庫ありなら在庫あり／取扱対象外は除外／在庫レコード無しは在庫切れ）: `tests/Feature/Front/Product/ProductStockDisplayTest.php`（正常系・異常系とも）
- 閲覧履歴の記録・更新・6件超の削除・未ログイン時の非記録: `tests/Feature/Front/Product/BrowsingHistoryTest.php`
- カート投入の正常系と異常系（未ログイン・在庫0・取扱対象外・非公開・在庫超過・加算・存在しないバリエーション）: `tests/Feature/Front/Cart/AddToCartTest.php`
- 規格の選択不可判定・カラー変更時のサイズ解除・SKUコードの表示: 自動テストなし。目視確認で担保する
- カート投入後にカートドロワーが開き、追加した明細が表示される: 自動テストなし。目視確認で担保する
- 送料モーダルの開閉と47都道府県の表示: 自動テストなし。目視確認で担保する
- Props型定義の整合性: `npx tsc --noEmit`
