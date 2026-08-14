# フロント カート

本書はカートの**価格方針**（現在価格の反映）・**概算送料の基準都道府県**・**購入不可行の扱い**の正本。購入手続き以降の仕様書はこれらを再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** カートに入れた商品の明細・数量変更・削除と、商品合計・概算送料・送料無料までの残額を提示し、購入手続きへ導く。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/cart` | `cart.index` | カートページ |
| PUT | `/cart/items/{cartItem}` | `cart.items.update` | 数量変更 |
| DELETE | `/cart/items/{cartItem}` | `cart.items.destroy` | 商品削除 |

`POST /cart/items`（カートへの投入）は [docs/front/product-show.md](product-show.md) が正本。

- **アクセス権限・ミドルウェア:** 全ルートを `auth`（`web` ガード）で保護する。未ログインのアクセスはログイン画面へ遷移する。数量変更・削除は `CartItemPolicy` を `can` ミドルウェアで適用し、自分のカート行以外は403。
- **本ドキュメントのスコープ:** カートページと明細の数量変更・削除。カートドロワーは [docs/front/common-layout.md](common-layout.md)、購入手続き以降は [docs/front/checkout.md](checkout.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `cart_items` | カート明細（`user_id` + `product_variant_id` で一意） |
| `product_variants` | 規格名・取扱可否 |
| `products` | 商品名・単価・公開状態 |
| `categories` | 画像未登録時のプレースホルダー（配色とシルエット）の判定に使うカテゴリ名 |
| `product_images` | 明細のサムネイル（メイン画像） |
| `stocks` | 在庫の二値判定と数量の上限 |
| `user_addresses` | 概算送料の基準にする既定の配送先 |
| `prefectures` / `shipping_settings` | 概算送料・都道府県名 |
| `ec_settings` | 送料無料しきい値 |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

明細あり（PCは2カラム、SP幅では1カラムに縦積み）:

```
+--------------------------------------------------------------+
| カート（h1）                                                   |
| +------------------------------+ +-------------------------+ |
| | [画] 商品名                   | | お支払い金額             | |
| |     レッド / M                | | 商品合計        ¥32,800 | |
| |     ¥14,800                  | | 概算送料（東京都）  ¥500 | |
| |     (−) 2 (＋)  削除  ¥29,600 | | 合計            ¥33,300 | |
| +------------------------------+ | あと ¥7,200 で送料無料   | |
| | [画] 商品名                   | | ====------------------  | |
| |     規格なし                  | | [  購入手続きへ進む  ]   | |
| |     ¥3,200                   | | [   買い物を続ける   ]   | |
| |     (−) 1 (＋)  削除   ¥3,200 | +-------------------------+ |
| +------------------------------+                             |
+--------------------------------------------------------------+
```

- 購入できない明細には理由（`在庫切れのため購入手続きに進めません` / `在庫が不足しています。数量を減らしてください` / `現在お取り扱いできません`）を行内に表示する。在庫数そのものは表示しない。
- 購入できない明細があるときは、サイドカードに「在庫が不足している商品があります。数量を変更するか、その商品を削除してください。」を表示し、「購入手続きへ進む」を非活性にする。
- 送料無料の進捗バーは達成率100%で `teal` に満ち、案内文が「送料無料が適用されます」に切り替わる。
- 明細が0件のときは、明細・サイドカードに代えて「カートに商品がありません」＋「商品を探す」（商品一覧へ）を表示する。

## インターフェース ＆ データロジック

- **カートページのProps:**

```ts
type CartRow = {
    id: number;                  // cart_items.id
    product_id: number;
    product_name: string;
    category_name: string;
    variant_label: string;       // 例: レッド / M（規格を持たない商品は「規格なし」）
    main_image_url: string | null;
    unit_price: number;          // 商品の現在価格
    quantity: number;
    subtotal: number;            // unit_price × quantity
    in_stock: boolean;           // 在庫 > 0
    is_purchasable: boolean;     // 公開中・取扱中・在庫が数量以上
    max_quantity: number;        // 在庫数と数量上限(99)のうち小さいほう
};

type Props = {
    items: CartRow[];
    subtotal: number;
    estimatedShippingLabel: string;    // 「無料」または「500円」
    estimatedTotal: number;
    freeShippingThreshold: number;
    remainingForFreeShipping: number;  // 0 なら送料無料が適用済み
    estimatedPrefectureName: string;   // 概算送料の基準にした都道府県名
};
```

- **入力値バリデーションルール（`UpdateCartItemRequest`）:**

| 項目 | ルール |
|---|---|
| `quantity` | 必須・整数・1以上・99以下・在庫数以下 |

在庫超過は `在庫が不足しています。` を `quantity` に返す。在庫はカートの表示時点から変わりうるため、上限は送信時にサーバー側で再検証する。

- **概算送料の算出:** 配送先が未確定のため「概算」として表示する。基準にする都道府県は、既定の配送先（`user_addresses.is_default = true`。複数あればID昇順の先頭）があればその都道府県、なければ東京都。支払方法は代引き手数料の付かない銀行振込で算出する。算出規則は [docs/shipping-calculation.md](../shipping-calculation.md) が正本。

- **主要な処理フロー:**

**カートページの表示**
1. 自分の `cart_items` を `variant.product.category` / `variant.product.mainImage` / `variant.stock` を eager load してID昇順で取得する（`CartService::rows()`。購入手続き・注文確認も同じ行の形を参照する）。
2. 各行の単価に商品の現在価格を用い、小計・商品合計を算出する。
3. 基準の都道府県で概算送料・合計・送料無料までの残額を算出する。
4. 購入できない行は `is_purchasable = false` として返し、フロントで理由を表示する。

**数量変更**
1. `CartItemPolicy::update` で自分のカート行であることを確認する（他人の行は403）。
2. 数量を検証する（上限は99かつ在庫数以下）。
3. `cart_items.quantity` を更新し、元の画面へ戻して `数量を変更しました` を表示する。

**商品削除**
1. `CartItemPolicy::delete` で確認する（他人の行は403）。
2. 明細を削除し、元の画面へ戻して `商品を削除しました` を表示する。

**購入手続きへ進む**
1. 明細が1件以上あり、すべての行が `is_purchasable = true` のときのみ遷移できる。満たさないときはボタンを非活性表示にする。
2. 遷移先は `checkout.index`（[docs/front/checkout.md](checkout.md)）。購入手続き画面側でも同じ条件を再判定し、満たさない場合はカートページへ戻す。

## 業務ルール

- カートは会員ごとにDBで保持し、未ログインのカートは持たない。カートページ自体もログインを要する。
- カート投入後に商品価格が変わった場合、カートには変更後の価格を反映する。価格が固定されるのは注文確定時。
- カート投入後に在庫切れ・在庫不足・非公開・取扱対象外になった行は、削除せず購入不可として残す（在庫の復活を待てるようにするため）。
- 概算送料は表示のみに使い、確定送料は購入手続きで配送先を選んだときに算出する。
- カートに有効期限は設けない（無期限に保持する）。
- 送料無料の達成に向けた商品提案は実装しない（残額の案内と進捗バーのみ）。

## 関連ドキュメント

- [docs/front/product-show.md](product-show.md) — カート投入と在庫の二値判定の正本
- [docs/front/checkout.md](checkout.md) — 購入手続き以降（配送先・支払い方法・注文確定）の正本
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout`・カートドロワー・共有プロパティの正本
- [docs/shipping-calculation.md](../shipping-calculation.md) — 送料・配達予定日・合計金額の算出の正本
- [docs/admin/ec-setting.md](../admin/ec-setting.md) — 送料無料しきい値の設定元
- [docs/admin/stock.md](../admin/stock.md) — 在庫数の更新元
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/CartController.php` |
| Controller | `app/Http/Controllers/Front/CartItemController.php` |
| FormRequest | `app/Http/Requests/Front/Cart/UpdateCartItemRequest.php` |
| Service | `app/Services/Front/Cart/CartService.php` |
| Policy | `app/Policies/CartItemPolicy.php` |
| Page | `resources/js/front/Pages/Cart/Index.tsx` |
| Component | `resources/js/front/Components/Cart/CartItemRow.tsx` |
| Component | `resources/js/front/Components/Cart/QuantityStepper.tsx` |
| Component | `resources/js/front/Components/Cart/CartSummary.tsx` |
| Component | `resources/js/front/Components/Cart/FreeShippingProgress.tsx` |
| Hook | `resources/js/front/hooks/useCartItem.ts` |
| Util | `resources/js/front/lib/freeShipping.ts` |
| Test | `tests/Feature/Front/Cart/CartIndexTest.php` |
| Test | `tests/Feature/Front/Cart/CartPriceTest.php` |
| Test | `tests/Feature/Front/Cart/CartShippingEstimateTest.php` |
| Test | `tests/Feature/Front/Cart/CartStockGuardTest.php` |
| Test | `tests/Feature/Front/Cart/UpdateCartItemTest.php` |
| Test | `tests/Feature/Front/Cart/DestroyCartItemTest.php` |

## 受け入れ条件

- 未ログインのカートページがログイン画面へ遷移する: `tests/Feature/Front/Cart/CartIndexTest.php`（`未ログインではログイン画面へリダイレクトされる`）
- 自分のカート行だけが表示される: 同上（`自分のカート行だけが表示される`）
- 空カートで明細0件・商品合計0になる: 同上（`カートが空のとき明細も合計も0になる`）
- 小計・商品合計が単価×数量で算出される: 同上（`明細の小計と商品合計が単価と数量から算出される`）
- 明細が増えてもクエリ数が変わらない（N+1が発生しない）: 同上（`明細が増えてもクエリ数が変わらない`）
- 商品価格の変更がカートの単価・小計・合計に反映される: `tests/Feature/Front/Cart/CartPriceTest.php`
- 概算送料の基準都道府県（既定の配送先あり／なし／既定でない住所のみ）と、しきい値と同額での無料判定・残額の算出: `tests/Feature/Front/Cart/CartShippingEstimateTest.php`（正常系・境界値とも）
- 在庫切れ・在庫不足・非公開・取扱対象外の行が購入不可になり、数量の上限が在庫数と99の小さいほうになる: `tests/Feature/Front/Cart/CartStockGuardTest.php`
- 数量変更の正常系と異常系（在庫超過・0以下・上限超過・他人の行403・未ログイン）: `tests/Feature/Front/Cart/UpdateCartItemTest.php`
- 削除の正常系と異常系（他人の行403・未ログイン）: `tests/Feature/Front/Cart/DestroyCartItemTest.php`
- 数量ステッパーの下限・上限での非活性、削除ボタンの動作: 自動テストなし。目視確認で担保する
- 送料無料の進捗バーの伸長と、達成時の配色・文言の切り替え: 自動テストなし。目視確認で担保する
- 購入不可の行がある場合に「購入手続きへ進む」が非活性になる: 自動テストなし。目視確認で担保する（サーバー側での再判定は `tests/Feature/Front/Checkout/CheckoutIndexTest.php` が担保する）
- Props型定義の整合性: `npx tsc --noEmit`
