# 単位15: フロント - カート

依存: 単位13, 単位10

## スコープ

- カートページ: 商品明細・数量変更・商品削除
- 商品合計・概算送料・「あと〇〇円で送料無料」案内・進捗バー
- 「購入手続きへ進む」（未ログイン時はログインを挟む）
- カートドロワー（ヘッダーのカートボタンから開く）の完成
- 購入手続き画面は単位16

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest） | `laravel-set:laravel` |
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
| GET | `/cart` | `cart.index` | 必要 | カートページ |
| PUT | `/cart/items/{cartItem}` | `cart.items.update` | 必要 | 数量変更 |
| DELETE | `/cart/items/{cartItem}` | `cart.items.destroy` | 必要 | 商品削除 |

`POST /cart/items`（カートに追加）は単位13で実装済み。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/CartController.php` | `index()` |
| Controller | `app/Http/Controllers/Front/CartItemController.php` | **修正**: `update()` / `destroy()` を追加 |
| FormRequest | `app/Http/Requests/Front/Cart/UpdateCartItemRequest.php` | 数量の検証（在庫数以下） |
| Service | `app/Services/Front/Cart/CartService.php` | カート明細の取得・商品合計の算出・概算送料の算出・送料無料までの残額の算出 |
| Policy | `app/Policies/CartItemPolicy.php` | 自分のカート行のみ操作できることを保証 |
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` | **修正**: `cartCount` を実データから算出 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/front/Pages/Cart/Index.tsx` | 左に商品明細、右にお支払い金額サイドカードの2カラム。空カート時は専用の表示 |
| Component | `resources/js/front/Components/Cart/CartItemRow.tsx` | 画像・商品名・バリエーション・単価・数量増減・削除・小計 |
| Component | `resources/js/front/Components/Cart/QuantityStepper.tsx` | マイナス / 数量 / プラス の丸型ステッパー |
| Component | `resources/js/front/Components/Cart/CartSummary.tsx` | 商品合計・概算送料・合計・送料無料案内・進捗バー・購入手続きボタン |
| Component | `resources/js/front/Components/Cart/FreeShippingProgress.tsx` | 「あと〇〇円で送料無料」＋進捗バー |
| Component | `resources/js/front/Components/CartDrawer.tsx` | **修正**: 明細・商品合計・送料無料案内・「カートを見る」を実データで描画 |

## インターフェース ＆ データロジック

### Props の型

```ts
type CartRow = {
  id: number;                    // cart_items.id
  product_id: number;
  product_name: string;
  variant_label: string;         // 'アクア / M' または '規格なし'
  main_image_url: string | null;
  unit_price: number;
  quantity: number;
  subtotal: number;              // unit_price * quantity
  in_stock: boolean;             // 在庫 > 0
  max_quantity: number;          // 在庫数（数量ステッパーの上限。在庫の実数を渡す）
};
type Props = {
  items: CartRow[];
  subtotal: number;
  estimatedShippingFee: number;  // 概算送料
  estimatedShippingLabel: string; // '無料' または '500円'
  estimatedTotal: number;
  freeShippingThreshold: number;
  remainingForFreeShipping: number;  // 0 なら送料無料が適用済み
  estimatedPrefectureName: string;   // 概算送料の基準にした都道府県名
};
```

### バリデーション

`UpdateCartItemRequest`:

| 項目 | ルール |
|---|---|
| `quantity` | 必須・整数・1以上・99以下・**在庫数以下** |

### 概算送料の算出

購入手続き前は配送先が確定していないため「概算」として表示する。

| 条件 | 基準にする都道府県 |
|---|---|
| ログイン中で既定の配送先がある | その配送先の都道府県 |
| ログイン中で配送先が未登録 | 東京都 |

商品合計が送料無料しきい値以上なら概算送料を `0`（「無料」）とする。`ShippingCalculator`（単位10）を使う。

### 主要な処理フロー

**カートページの表示**:
1. `cart_items` を `variant.product.category` / `variant.product.mainImage` / `variant.stock` を eager load して取得する（N+1 を作らない）
2. 各行の単価は `products.price` の**現在値**を使う（カートは注文確定前のためスナップショットにしない）
3. 商品合計・概算送料・合計・送料無料までの残額を算出する
4. 在庫切れになった行は `in_stock: false` として表示し、購入手続きへ進めないようにする
5. カートが空なら「カートに商品がありません」＋「商品を探す」ボタンを表示する

**数量変更**:
1. `CartItemPolicy` で自分のカート行であることを確認する
2. 在庫数を超えないことを検証する
3. `cart_items.quantity` を更新する → カートページへリダイレクト

**商品削除**: `CartItemPolicy` で確認 → 削除 → 「商品を削除しました」をフラッシュ

**購入手続きへ進む**:
1. 未ログインならログイン画面へ（ログイン後にカートへ復帰）
2. カートが空なら進めない
3. 在庫切れ・在庫不足の行があれば「在庫が不足している商品があります」を表示して進めない
4. 問題なければ `/checkout` へ遷移する（単位16）

### 進捗バー

送料無料までの達成率を `min(100, 商品合計 ÷ しきい値 × 100)` で表す。100%到達時はバーを `teal` で満たし、案内文を「送料無料が適用されます」に切り替える。

## 業務ルール

- カートは会員ごとにDBで保持する（ログインが必要）。未ログインのカートは持たない
- カートに入れた後に商品価格が変更された場合、カートには**変更後の価格**が反映される。価格が固定されるのは注文確定時である
- カートに入れた後に商品が非公開になった場合、または在庫切れになった場合は、その行を購入不可として表示し購入手続きへ進めない
- 数量の上限は99、かつ在庫数以下
- 概算送料は表示のみに使う。確定送料は購入手続き画面で配送先を選択したときに算出する

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/Cart/CartIndexTest.php` | 未ログインでログイン画面へリダイレクトされること／自分のカート行のみ表示されること／空カートの表示／商品合計の算出／N+1が発生しないこと |
| `tests/Feature/Front/Cart/CartPriceTest.php` | カート投入後に商品価格を変更すると、カートの単価・小計・合計が変更後の価格になること |
| `tests/Feature/Front/Cart/CartShippingEstimateTest.php` | 既定の配送先の都道府県で概算送料が算出されること／配送先未登録なら東京都基準になること／しきい値以上で概算送料が0になること／しきい値と同額のとき0になること（境界値）／残額の算出 |
| `tests/Feature/Front/Cart/UpdateCartItemTest.php` | 数量変更ができること／在庫数を超える数量を弾くこと／0以下を弾くこと／他人のカート行を変更できないこと（403） |
| `tests/Feature/Front/Cart/DestroyCartItemTest.php` | 削除できること／他人のカート行を削除できないこと（403） |
| `tests/Feature/Front/Cart/CartStockGuardTest.php` | 在庫切れになった行が購入不可として表示されること／非公開になった商品の行が購入不可として表示されること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **未ログインのカート**: 推奨=**持たない**（会員必須サイトのため）。カートページ自体もログインが必要。商品詳細の「カートに入れる」でログイン画面へ遷移し、ログイン後に元の商品詳細へ復帰する。
2. **概算送料の基準都道府県**: 要件は「概算送料」とだけ記載。モックは「東京都」固定。推奨=**既定の配送先があればその都道府県、なければ東京都**。
3. **在庫切れ商品のカート内での扱い**: 要件に記載なし。推奨=**行は残したまま購入不可として表示する**（自動削除しない。会員が在庫復活を待てるようにする）。
4. **価格変更時のカートの扱い**: 推奨=**現在価格を反映する**（カートは確定前の状態であり、価格の固定は注文確定時に行うため）。カート投入時の価格で固定したい場合は実行時に指示すること。
5. **送料無料達成に向けた商品提案**: 要件で「引き続き検討」。推奨=**実装しない**（進捗バーと残額案内のみ）。
6. **カートの有効期限**: 実装しない（無期限に保持する）。
7. **数量ステッパーの上限表示**: 在庫数を数量の上限としてフロントに渡す。在庫数そのものを画面に表示はしない（在庫は二値表示の原則を守る）。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で `docs/front/cart.md` を作成する（カートの価格方針・概算送料・在庫切れ時の扱いの**正本**）。`docs/shipping-calculation.md` へリンクする
- 本計画ファイルを削除し、トラッカーの状態を更新する
