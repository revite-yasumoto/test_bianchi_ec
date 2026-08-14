# 商品登録・編集・削除

本ドキュメントは商品の登録／編集フォーム、**SKU一覧の自動生成**、**商品画像のアップロード**の正本。単位06以降の在庫管理・CSV取込の仕様書は、これらの仕組みを再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** 商品の基本情報・画像・スペック・SKU（バリエーション）・在庫を1画面で登録／編集する。削除もこの画面から行う。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/products/create` | `admin.products.create` | `auth:admin` |
| POST | `/admin/products` | `admin.products.store` | `auth:admin` |
| GET | `/admin/products/{product}/edit` | `admin.products.edit` | `auth:admin` |
| PUT | `/admin/products/{product}` | `admin.products.update` | `auth:admin` |
| DELETE | `/admin/products/{product}` | `admin.products.destroy` | `auth:admin` |

画像を含むため送信は `multipart/form-data` になる。更新は `_method=put` によるメソッドスプーフィングで送る（PUT では FormData を送れないため）。

- **アクセス権限・ミドルウェア:** 上表の通り。
- **本ドキュメントのスコープ:** 登録・編集・削除の全体。一覧・絞り込みは [docs/admin/product-index.md](product-index.md) が正本。

## 使用テーブル

`products` / `product_images` / `product_specs` / `product_variants` / `stocks` を書き込み、`categories`・`spec_options` を選択肢として読む。定義は [docs/2_database.md](../2_database.md) が正本。

- `product_images` は `(product_id, sort_order)` に一意制約を持つ。
- `product_variants` は `(product_id, size_name, color_name)` に一意制約、`sku_code` にテーブル全体の一意制約を持つ。
- `stocks.product_variant_id` は一意。バリエーション削除時に在庫も cascade で消える。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
2カラム（幅が足りない場合は縦積み）

左カラム                          右カラム
+---------------------------+    +---------------------------+
| 基本情報                   |    | SKU（バリエーション） [ON] |
| 商品ID     | 価格（税込）  |    |---------------------------|
| 商品名（全幅）             |    | カラー: [ネイビー×][アクア×]|
| カテゴリ   | 公開状態      |    |  規格管理から追加：＋レッド |
| 商品説明（全幅）           |    | サイズ: [M×][L×]          |
+---------------------------+    |  規格管理から追加：＋S ＋XL|
| 商品画像                   |    +---------------------------+
| +--------+ [1][2][3][4]   |    | SKU一覧（自動生成） 4件    |
| | メイン | [5][6][7][8][9]|    | カラー|サイズ|枝番|コード  |
| +--------+                |    |       |      |    |在庫|取扱|
+---------------------------+    +---------------------------+
| 商品スペック表             |    | [    保存する    ][キャンセル]|
| [項目名][内容]      削除   |    | この商品を削除する          |
| ＋ 項目を追加              |    +---------------------------+
+---------------------------+

SKUなし（トグルOFF）のときは右カラムが「在庫数」の単一入力欄に変わる。
```

- 各入力欄のエラーはその直下にインラインで表示する。枝番のエラーはSKU一覧の該当行に表示する。
- 画像のエラーは、枚数の上限（`images`）と1枚ごとの形式・容量・寸法（`images.0` のように枚ごとのキーで返る）のどちらも画像欄に表示する。どちらか一方しか拾わないと、弾かれているのに画面に何も出ない状態になる。
- 削除は確認モーダル（`ConfirmDialog`）を経て実行する。
- 画像はドラッグ＆ドロップとファイル選択の双方で追加できる。追加した画像はアップロード前にプレビュー表示される。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type ProductFormData = {
    id: number;
    product_code: string;
    name: string;
    category_id: number;
    price: number;
    description: string | null;
    is_published: boolean;
    has_sku: boolean;
    images: { id: number; url: string }[];
    specs: { label: string; value: string }[];
    variants: {
        size_name: string | null;
        color_name: string | null;
        branch_code: string | null;
        is_available: boolean;
        quantity: number;
    }[];
};

type Props = {
    product: ProductFormData | null;   // null = 新規登録
    categories: { id: number; name: string }[];
    sizeOptions: string[];             // 規格管理に登録済みのサイズ
    colorOptions: string[];            // 規格管理に登録済みのカラー
};

type SkuRow = {
    key: string;            // `${color}|${size}`
    color_name: string;
    size_name: string;
    branch_code: string;
    sku_code: string;       // 表示用。取扱対象外は `-`
    quantity: number;
    is_available: boolean;
};
```

- **入力値バリデーションルール（`Admin\Product\StoreProductRequest` / `UpdateProductRequest`）:**

| 項目 | ルール |
|---|---|
| `product_code` | 必須・文字列・最大50・半角英数とハイフンのみ・`products.product_code` で一意（更新時は自身を除外） |
| `name` | 必須・文字列・最大255 |
| `category_id` | 必須・整数・`categories.id` に存在 |
| `price` | 必須・整数・0以上・9,999,999以下 |
| `description` | 任意・文字列・最大5000 |
| `is_published` / `has_sku` | 必須・真偽値 |
| `images` | 任意・配列。**既存の残り＋新規の合計**が10枚以下 |
| `images.*` | 画像・jpg / jpeg / png / webp・5MB以下・最大辺4000px |
| `deleted_image_ids` | 任意・配列・各要素は整数 |
| `specs` | 任意・配列・最大20要素 |
| `specs.*.label` / `specs.*.value` | 必須・最大100 / 最大255 |
| `variants` | 必須・配列・1要素以上 |
| `variants.*.is_available` | 必須・真偽値 |
| `variants.*.size_name` / `color_name` | 任意・最大50。`has_sku` が true のとき必須 |
| `variants.*.branch_code` | 任意・最大20・半角英数。`has_sku` かつ取扱ありのとき必須、同一商品内で一意 |
| `variants.*.quantity` | 任意・整数・0以上・999,999以下。取扱ありのとき必須 |

配列内の相関チェック（取扱ありの行の必須項目・枝番の商品内重複・画像枚数の合算）はルール記法で表せないため `withValidator()` で行う。

- **主要な処理フロー（`ProductSaveService::save()`。全体を1トランザクションで囲む）:**

1. `products` を作成または更新する。
2. `SyncProductImages` で画像を同期する（後述）。
3. `product_specs` を全削除して送信順に再作成する（`sort_order` は0から連番）。
4. `SyncProductVariants` でバリエーションと在庫を同期する（後述）。

- **削除（`ProductSaveService::delete()`）:** 画像の実ファイルをストレージから削除してから `products` を削除する。`product_images` / `product_specs` / `product_variants` / `stocks` / `favorites` / `browsing_histories` / `cart_items` は cascade で消える。

### SKU一覧の自動生成（`useSkuMatrix`）

- カラー配列とサイズ配列の直積で行を生成する。並びはカラーが外側、サイズが内側。
- 枝番の既定値は `(カラーの並び順 + 1) × 10 + サイズの並び順 + 1`。ユーザーが上書きできる。
- SKUコードは `商品ID-枝番` をリアルタイムに組み立てて表示する。取扱対象外の行は `-` を表示する。
- 取扱対象外に切り替えた行は枝番・在庫の入力欄を無効化し、行を淡色にする。
- 入力値は組み合わせキー（`カラー|サイズ`）で保持するため、カラー・サイズを増減しても既に入力した枝番・在庫は失われない。
- カラー・サイズに選べる値は規格管理（[docs/admin/spec-option.md](spec-option.md)）に登録済みのものに限る。

### バリエーションの同期（`SyncProductVariants`）

- **`has_sku = false`**: サイズ・カラーを持たないバリエーション1件に集約する。`sku_code` は商品IDそのもの、`branch_code` は `null`、取扱ありとする。サイズ・カラーを持つ既存のバリエーションは削除する。
- **`has_sku = true`**: 送信された組み合わせを正とする。
  - 既存にあり送信にない組み合わせ → 削除（在庫も cascade で削除）
  - 送信にあり既存にない組み合わせ → 作成
  - 双方にある組み合わせ → 枝番・SKUコード・取扱状態・在庫を更新
- 取扱対象外（`is_available = false`）の行は `sku_code` を `null`、在庫を0にする。
- `sku_code` はテーブル全体で一意のため、商品IDの変更や枝番の入れ替えで更新途中に旧値と新値が衝突しうる。更新前に対象商品の `sku_code` を一旦すべて `null` に解放してから割り当て直す。
- 在庫は `stocks` を `product_variant_id` 単位で作成または更新する。

### 画像の同期（`SyncProductImages`）

- 削除指定された画像は実ファイルとレコードの双方を削除する。
- 残った既存画像（表示順）→ 新規アップロードの順に並べ、`sort_order` を0から振り直す。`sort_order = 0` がメイン画像。
- `(product_id, sort_order)` に一意制約があるため、振り直しの前に既存の `sort_order` を退避値（100以上）へ移してから0から割り当てる。
- 保存先は `public` ディスクの `products/{product_id}/`。ファイル名は Laravel の自動生成に任せる。
- アップロードされた画像のリサイズ・形式変換は行わない。

## 業務ルール

- 在庫はこのフォームのSKU単位の入力欄からのみ登録する。商品マスタに単一の在庫カラムは持たない（在庫マスタへの一元化）。
- SKUなし商品もバリエーションを1件持つ。これによりカート・注文明細・在庫管理がSKUの有無で分岐しない。
- 取扱対象外の組み合わせは、商品詳細のカラー／サイズ選択肢で取り消し線＋グレーアウトの対象になる（単位13で実装）。
- 注文実績のある商品も削除できる。`order_items.product_id` は `ON DELETE SET NULL` で、注文明細は商品情報のスナップショットを保持しているため過去の注文表示は壊れない。
- 商品名・カテゴリの変更は過去の注文明細に影響しない（同じくスナップショット）。

## 関連ドキュメント

- [docs/admin/product-index.md](product-index.md) — 商品一覧の正本
- [docs/admin/spec-option.md](spec-option.md) — カラー・サイズの選択肢を管理する規格管理
- [docs/admin/category.md](category.md) — カテゴリの選択肢を管理するカテゴリ管理
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`ConfirmDialog` の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/ProductController.php` |
| FormRequest | `app/Http/Requests/Admin/Product/BaseProductRequest.php` |
| FormRequest | `app/Http/Requests/Admin/Product/StoreProductRequest.php` |
| FormRequest | `app/Http/Requests/Admin/Product/UpdateProductRequest.php` |
| Service | `app/Services/Admin/Product/ProductSaveService.php` |
| Action | `app/Actions/Admin/Product/SyncProductVariants.php` |
| Action | `app/Actions/Admin/Product/SyncProductImages.php` |
| Model | `app/Models/Product.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Product/Form.tsx` |
| Hook | `resources/js/admin/hooks/useProductForm.ts` |
| Hook | `resources/js/admin/hooks/useSkuMatrix.ts` |
| Component | `resources/js/admin/Components/Product/BasicInfoCard.tsx` |
| Component | `resources/js/admin/Components/Product/ImageUploader.tsx` |
| Component | `resources/js/admin/Components/Product/SkuToggle.tsx` |
| Component | `resources/js/admin/Components/Product/VariationEditor.tsx` |
| Component | `resources/js/admin/Components/Product/SkuTable.tsx` |
| Component | `resources/js/admin/Components/Product/SpecEditor.tsx` |
| Test | `tests/Feature/Admin/Product/ProductStoreTest.php` |
| Test | `tests/Feature/Admin/Product/ProductUpdateTest.php` |
| Test | `tests/Feature/Admin/Product/ProductValidationTest.php` |
| Test | `tests/Feature/Admin/Product/ProductImageTest.php` |
| Test | `tests/Feature/Admin/Product/ProductDestroyTest.php` |

## 受け入れ条件

- 登録画面が表示される: `tests/Feature/Admin/Product/ProductStoreTest.php`（`商品登録画面が表示される`）
- SKUなし商品でバリエーションと在庫が1件作られる: 同上（`バリエーションなし商品を登録すると在庫が1件作られる`）
- SKUあり商品で組み合わせの数だけ作られ、SKUコードが組み立てられる: 同上（`バリエーションあり商品を登録すると組み合わせの数だけ作られる`）
- 取扱対象外の行はSKUコードが `null` で在庫0になる: 同上（`取扱対象外の組み合わせはコードが空で在庫が0になる`）
- スペックが並び順付きで保存される: 同上（`商品スペックが並び順付きで保存される`）
- 編集画面に既存の値が渡る: `tests/Feature/Admin/Product/ProductUpdateTest.php`（`商品編集画面に既存の値が渡される`）
- 組み合わせの増減がバリエーション・在庫に反映される: 同上（`組み合わせを減らすと該当のバリエーションと在庫が削除される`・`組み合わせを増やすとバリエーションと在庫が作られる`）
- 組み合わせを変えても既存の在庫が保持される: 同上（`組み合わせを変えても既存の在庫は保持される`）
- 商品ID変更・枝番入れ替えでSKUコードが組み立て直される: 同上（`商品識別コードを変えると全てのコードが組み立て直される`・`枝番を入れ替えても保存できる`）
- SKUあり→なしの切り替えで1件に集約される: 同上（`規格ありから規格なしへ切り替えるとバリエーションが1件に集約される`）
- 更新時に自身の商品IDは重複扱いにならず、他商品との重複は弾かれる: 同上（`自身の商品識別コードは重複として扱われない`・`他の商品と同じ商品識別コードには変更できない`）
- 各入力値のバリデーション: `tests/Feature/Admin/Product/ProductValidationTest.php`（商品ID重複・記号・商品名必須・カテゴリ存在・価格範囲・サイズ必須・枝番重複・枝番必須・バリエーション必須・画像枚数・拡張子・スペック必須の13件）
- 画像の表示順・実ファイルの保存と削除: `tests/Feature/Admin/Product/ProductImageTest.php`（表示順0からの採番・詰め直し・ストレージへの保存と削除・既存＋新規の上限）
- 商品削除で関連データと実ファイルが消え、注文明細は残る: `tests/Feature/Admin/Product/ProductDestroyTest.php`（`商品を削除するとバリエーションと在庫も消える`・`商品を削除すると画像の実ファイルも消える`・`注文実績のある商品を削除しても注文明細は残る`）
- SKU一覧の自動生成・枝番の既定値・入力値の保持: 自動テストなし。目視確認で担保する
- 画像のドラッグ＆ドロップ・プレビュー: 自動テストなし。目視確認で担保する
- 削除確認モーダルの表示・キャンセル: 自動テストなし。目視確認で担保する
- 2カラムのレスポンシブ表示: 自動テストなし。目視確認で担保する
