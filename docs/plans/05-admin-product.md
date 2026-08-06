# 単位05: 管理画面 - 商品管理（一覧・登録／編集）

依存: 単位04

本プロジェクトで最も複雑な単位。SKUの有無で登録内容が動的に切り替わり、SKU一覧を組み合わせから自動生成する。

## スコープ

- 商品一覧: 絞り込み（商品名/商品ID・カテゴリ・SKU有無・価格帯）・クリアボタン・件数表示・編集導線
- 商品登録／編集フォーム: 基本情報・商品画像（最大10枚）・SKU設定・SKU一覧の自動生成・SKU単位の在庫入力
- 商品削除
- CSVインポート／エクスポートは単位09

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Action / Service） | `laravel-set:laravel` |
| クエリビルダ・トランザクション | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/products` | `admin.products.index` | 商品一覧 |
| GET | `/admin/products/create` | `admin.products.create` | 商品登録フォーム |
| POST | `/admin/products` | `admin.products.store` | 商品登録 |
| GET | `/admin/products/{product}/edit` | `admin.products.edit` | 商品編集フォーム |
| PUT | `/admin/products/{product}` | `admin.products.update` | 商品更新 |
| DELETE | `/admin/products/{product}` | `admin.products.destroy` | 商品削除 |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/ProductController.php` | `index()` / `create()` / `store()` / `edit()` / `update()` / `destroy()` |
| FormRequest | `app/Http/Requests/Admin/Product/StoreProductRequest.php` | 基本情報・画像・SKU・在庫のバリデーション |
| FormRequest | `app/Http/Requests/Admin/Product/UpdateProductRequest.php` | 同上（`product_code` の一意チェックで自身を除外） |
| Service | `app/Services/Admin/Product/ProductSaveService.php` | 商品・画像・スペック・バリエーション・在庫をトランザクションで一括保存 |
| Action | `app/Actions/Admin/Product/SyncProductVariants.php` | サイズ×カラーの組み合わせから `product_variants` と `stocks` を同期 |
| Action | `app/Actions/Admin/Product/SyncProductImages.php` | 画像の保存・差し替え・削除と `sort_order` の再割り当て |
| Action | `app/Actions/Admin/Product/BuildProductFilter.php` | 商品一覧の絞り込みクエリの組み立て |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Product/Index.tsx` | 絞り込みバー＋一覧テーブル |
| Page | `resources/js/admin/Pages/Product/Form.tsx` | 登録／編集の共用フォーム。左カラムに基本情報＋商品画像、右カラムにSKU設定＋SKU一覧＋保存ボタン |
| Component | `resources/js/admin/Components/Product/ImageUploader.tsx` | メイン画像1枚（180×180）＋サブ画像9枚（正方形グリッド）。ドラッグ＆ドロップ・プレビュー・削除 |
| Component | `resources/js/admin/Components/Product/SkuToggle.tsx` | SKU有無のトグルスイッチ |
| Component | `resources/js/admin/Components/Product/VariationEditor.tsx` | カラー・サイズのタグ追加／削除＋規格管理からのワンクリック追加 |
| Component | `resources/js/admin/Components/Product/SkuTable.tsx` | 組み合わせ表（カラー・サイズ・枝番・SKUコード・在庫・取扱トグル） |
| Component | `resources/js/admin/Components/Product/SpecEditor.tsx` | 商品スペック表の項目追加／削除 |
| Hook | `resources/js/admin/hooks/useSkuMatrix.ts` | カラー配列×サイズ配列からSKU行を生成し、枝番・在庫・取扱状態を保持する |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「商品一覧」「商品登録」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
// Product/Index.tsx
type ProductRow = {
  id: number;
  product_code: string;
  name: string;
  category_name: string;
  price: number;
  total_stock: number;   // SKUあり=全バリエーションの合計、なし=単一在庫
  has_sku: boolean;
  is_published: boolean;
};
type Filters = {
  q: string | null;
  category_id: number | null;
  has_sku: 'all' | 'with' | 'without';
  price_min: number | null;
  price_max: number | null;
};
type Props = {
  products: Paginated<ProductRow>;
  categories: { id: number; name: string }[];
  filters: Filters;
  totalCount: number;   // 絞り込み前の全件数
};

// Product/Form.tsx
type SkuRow = {
  key: string;            // `${color}|${size}`
  color_name: string | null;
  size_name: string | null;
  branch_code: string;
  sku_code: string;       // `${product_code}-${branch_code}`。取扱対象外は '-'
  quantity: number;
  is_available: boolean;
};
type Props = {
  product: ProductFormData | null;   // null = 新規登録
  categories: { id: number; name: string }[];
  sizeOptions: string[];             // 規格管理に登録済みのサイズ
  colorOptions: string[];            // 規格管理に登録済みのカラー
};
```

### バリデーション

`StoreProductRequest` / `UpdateProductRequest`:

| 項目 | ルール |
|---|---|
| `product_code` | 必須・文字列・最大50・半角英数とハイフンのみ・`products.product_code` で一意 |
| `name` | 必須・文字列・最大255 |
| `category_id` | 必須・`categories.id` に存在 |
| `price` | 必須・整数・0以上・9,999,999以下 |
| `description` | 任意・文字列・最大5000 |
| `is_published` | 必須・boolean |
| `has_sku` | 必須・boolean |
| `images` | 任意・配列・最大10要素 |
| `images.*` | 画像ファイル（jpg / jpeg / png / webp）・最大5MB・最大辺4000px |
| `specs` | 任意・配列・最大20要素 |
| `specs.*.label` | 必須・最大100 |
| `specs.*.value` | 必須・最大255 |
| `variants` | 必須・配列・最小1要素 |
| `variants.*.size_name` | `has_sku` が true のとき必須・最大50 |
| `variants.*.color_name` | `has_sku` が true のとき必須・最大50 |
| `variants.*.branch_code` | `has_sku` かつ取扱ありのとき必須・最大20・半角英数・同一商品内で一意 |
| `variants.*.is_available` | 必須・boolean |
| `variants.*.quantity` | 取扱ありのとき必須・整数・0以上・999,999以下 |

### 主要な処理フロー

**商品一覧**:
1. `products` に `category` を eager load し、`variants.stock` の在庫合計をサブクエリで付与する（N+1 を作らない）
2. 絞り込みを適用する
   - `q`: `name` LIKE 部分一致 **OR** `product_code` LIKE 部分一致
   - `category_id`: 完全一致
   - `has_sku`: `with` / `without` を boolean に変換して一致（`all` は条件なし）
   - `price_min` / `price_max`: `price` の範囲。**`price` は整数列なので比較値も整数にキャストして渡す**（文字列と数値の比較を避ける）
3. `id` の降順でページネーション（1ページ50件）
4. 件数表示は「絞り込み後の件数 / 全N件」

**商品登録／編集の保存**（`ProductSaveService`。全体を1トランザクションで囲む）:
1. `products` を作成／更新する
2. `SyncProductImages` で画像を処理する
   - 新規アップロード分を `storage/app/public/products/{product_id}/` に保存
   - 削除指定された画像をストレージとレコードから削除
   - 残った画像の `sort_order` を 0..9 に振り直す（0 = メイン画像）
3. `product_specs` を全削除して再作成する
4. `SyncProductVariants` でバリエーションを同期する
   - `has_sku = false`: `size_name` / `color_name` を `null`、`sku_code` を `product_code` としたバリエーション1件に集約する。既存の他のバリエーションは削除する
   - `has_sku = true`: 送信されたカラー×サイズの組み合わせを正とする
     - 既存にあり送信にない組み合わせ → **削除**（在庫も cascade で削除）
     - 送信にあり既存にない組み合わせ → 作成（在庫は入力値、なければ0）
     - 双方にある組み合わせ → `branch_code` / `sku_code` / `is_available` / 在庫を更新
   - `is_available = false`（規格なし）の行は `sku_code` を `null`、在庫を 0 にする
   - `sku_code` は `{product_code}-{branch_code}` で組み立てる。`product_code` が変更された場合は全バリエーションの `sku_code` を再組み立てする
5. `stocks` を `product_variant_id` 単位で upsert する

**商品削除**:
1. 確認モーダルで確認する
2. その商品を含む `order_items` があっても削除を許可する（`order_items.product_id` は `ON DELETE SET NULL`。注文明細はスナップショットを保持しているため過去注文の表示は壊れない）
3. `product_images` の実ファイルをストレージから削除する
4. `products` を削除する（`product_images` / `product_specs` / `product_variants` / `stocks` / `favorites` / `browsing_histories` / `cart_items` は cascade で削除される）

### SKU一覧の自動生成（フロント側 `useSkuMatrix`）

- カラー配列とサイズ配列の直積で行を生成する。行の並びはカラー外側・サイズ内側
- 枝番の既定値は `(カラーのindex + 1) × 10 + サイズのindex + 1`（モックと同じ規則）。ユーザーが上書きできる
- SKUコードは `商品ID-枝番` をリアルタイムに組み立てて表示する。取扱対象外の行は `-` を表示する
- 取扱対象外に切り替えた行は、在庫入力欄を `disabled` にし、行全体の不透明度を下げる
- カラー／サイズを追加・削除しても、既に入力済みの枝番・在庫は組み合わせキー（`色|サイズ`）で保持する

## 業務ルール

- 在庫は本フォームのSKU単位の入力欄からのみ登録する。商品マスタに単一の在庫カラムは持たない（在庫マスタへの一元化）
- SKUなし商品もバリエーション1件を持つ。これによりカート・注文明細・在庫管理がSKUの有無で分岐しない
- 「規格なし（取扱対象外）」の組み合わせは商品詳細のカラー／サイズ選択肢で取り消し線＋グレーアウトの対象になる（単位13）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Product/ProductIndexTest.php` | 絞り込みの各条件（商品名・商品ID・カテゴリ・SKU有無・価格帯）が効くこと／組み合わせ条件／件数表示／在庫合計の算出 |
| `tests/Feature/Admin/Product/ProductStoreTest.php` | SKUなし商品の登録（バリエーション1件・在庫1件が作られること）／SKUあり商品の登録（組み合わせ数のバリエーションと在庫が作られること）／`sku_code` の組み立て／取扱対象外の行は `sku_code` が `null` で在庫が0になること |
| `tests/Feature/Admin/Product/ProductValidationTest.php` | 商品IDの重複／必須項目の欠落／価格の範囲外／枝番の商品内重複／画像11枚目の拒否／許可外拡張子の拒否 |
| `tests/Feature/Admin/Product/ProductUpdateTest.php` | カラー／サイズを減らしたときに該当バリエーションが削除されること／増やしたときに作成されること／既存の在庫が保持されること／`product_code` 変更時に全 `sku_code` が再組み立てされること／SKUあり→なしへの切り替え |
| `tests/Feature/Admin/Product/ProductImageTest.php` | メイン画像が `sort_order = 0` になること／削除後に `sort_order` が詰められること／実ファイルが保存・削除されること（`Storage::fake()` を使う） |
| `tests/Feature/Admin/Product/ProductDestroyTest.php` | 注文実績のある商品を削除しても `order_items` が残り `product_id` が `null` になること／画像の実ファイルが削除されること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **画像の保存先**: 推奨=`public` ディスク（`storage/app/public/products/{product_id}/`）。`php artisan storage:link` の実行が必要なため、実装完了報告で案内する。
2. **画像のリサイズ・WebP変換**: 推奨=**行わない**（アップロードされたファイルをそのまま保存する）。要件に記載がなく、画像処理ライブラリの追加も避ける。必要なら別単位で `webp-convert` スキルを使って追加する。
3. **一覧のページネーション**: 要件に記載はないが、商品件数の増加に備えて推奨=**1ページ50件のページネーションを実装する**。
4. **注文実績のある商品の削除**: 推奨=**許可する**（スナップショット設計により過去注文が壊れないため）。
5. **公開状態と在庫切れの関係**: 在庫0でも「公開」のまま商品一覧・詳細に表示し、「在庫切れ」バッジと購入不可で表す（要件どおり）。自動で非公開にはしない。
6. **商品スペック表**: 要件は「下部に商品説明・商品スペック表を掲載」とだけ定めており、管理画面の入力UIはモックに存在しない。推奨=**`SpecEditor` として項目追加／削除のUIを実装する**（データがなければフロントに何も出せないため）。
7. **絞り込みの保持**: 推奨=クエリストリングで保持し、編集から一覧へ戻ったときに絞り込み条件が維持されるようにする。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/product-index.md`
  - `docs/admin/product-form.md`（SKU自動生成・画像アップロードの**正本**）
- 本計画ファイルを削除し、トラッカーの状態を更新する
