# カテゴリ管理

## 機能概要

- **対象画面・機能の目的:** 商品カテゴリの一覧・追加・削除と、TOPのカテゴリ入口カードに出す画像の設定。登録したカテゴリは商品登録の選択肢、商品一覧・在庫の絞り込みに使われる。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/categories` | `admin.categories.index` | `auth:admin` |
| POST | `/admin/categories` | `admin.categories.store` | `auth:admin` |
| DELETE | `/admin/categories/{category}` | `admin.categories.destroy` | `auth:admin` |
| PUT / PATCH | `/admin/categories/{category}/image` | `admin.categories.image.update` | `auth:admin` |
| DELETE | `/admin/categories/{category}/image` | `admin.categories.image.destroy` | `auth:admin` |

画像のルートはカテゴリごとに1つのサブリソースとして `Route::singleton('categories.image', ...)` で定義する（`update`・`destroy` のみ）。画像の登録は `multipart/form-data` で送るため、`_method=put` によるメソッドスプーフィングで送る。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** カテゴリの一覧・追加・削除と画像の登録・差し替え・削除。名称の変更・並び替えのUIは実装しない。画像のフロントでの表示は [docs/front/top.md](../front/top.md) が正本。画面が使う共通レイアウトと `MasterListCard` は [docs/admin/common-layout.md](common-layout.md) が正本。

## 使用テーブル

`categories` テーブルを使用し、登録商品件数の算出に `products` を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

`products.category_id` は `ON DELETE RESTRICT`。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+--------------------------------------------------+  ← 最大幅560px
| 登録したカテゴリは商品登録の選択肢・商品一覧／在庫  |
| の絞り込みに反映されます。                          |
|--------------------------------------------------|
| [画] ロードバイク   12商品           画像   削除   |
| [－] ヘルメット      3商品           画像   削除   |
| [－] ウェア          0商品           画像   削除   |
|--------------------------------------------------|
| [例：ヘルメット              ]  [   追加   ]      |
+--------------------------------------------------+
```

画像モーダル（行の「画像」で開く）:

```
+ 「ロードバイク」の画像 -------------------- × +
| [現在の画像 / 選んだ画像のプレビュー（4:3）]   |
| [ファイルを選択]                              |
| [   保存する   ]        [ 画像を削除 ]         |
+-----------------------------------------------+
```

- 各行の先頭に登録済み画像のサムネイル（代替テキスト「登録済みの画像」）を出し、未登録なら空の枠を出す（読み上げでは「画像未登録」と伝える）。モーダルのプレビューの代替テキストは「登録済みの画像」「選択した画像のプレビュー」とする。
- 行のサムネイルと「画像」ボタンは `MasterListCard` の `renderLeading` / `renderActions` で差し込む。
- 画像モーダルには「TOPのカテゴリ入口カードに表示します。未登録の場合は既定の配色とシルエットで表示します。」と説明を添える。「画像を削除」は画像が登録されているときだけ出す。
- 画像の保存・削除の完了後はモーダルを閉じ、トースト（「画像を保存しました」「画像を削除しました」）を表示する。画像のバリデーションエラーはファイル選択欄の下に表示し、モーダルは開いたままにする。
- 追加時のバリデーションエラーは追加フォームの直下にインラインで表示する。
- 削除は確認モーダル（`ConfirmDialog`）を経てから実行する。削除できなかった場合の理由はトーストで表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type CategoryRow = {
    id: number;
    name: string;
    product_count: number;
    image_url: string | null;
};
type Props = { categories: CategoryRow[] };
```

- **入力値バリデーションルール:**

`Admin\Category\StoreCategoryRequest`（追加）:

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大50・`categories.name` で一意 |

`Admin\Category\UpdateCategoryImageRequest`（画像の登録）:

| 項目 | ルール |
|---|---|
| `image` | 必須・画像・jpg / jpeg / png / webp・5MB以下・最大辺4000px（商品画像と共通の定義 `App\Support\ImageUploadRules`） |

- **主要な処理フロー:**

**一覧（`index()`）:** `categories` を `sort_order` 昇順 → `id` 昇順で取得し、`withCount('products')` で登録商品件数を付与して `id`・`name`・`product_count`・`image_url`（`asset('storage/<パス>')`、未登録は `null`）を渡す。

**画像の登録（`CategoryImageController::update()`）:** バリデーション → `CategoryImageService::replace()` が画像を `public` ディスクの `categories/` に保存してパスを更新し、更新後に旧ファイルを削除する → 直前の画面へ戻る。

**画像の削除（`CategoryImageController::destroy()`）:** `CategoryImageService::remove()` がパスを `null` にした後、ファイルを削除する → 直前の画面へ戻る。

**追加（`store()`）:** バリデーション → `sort_order` に既存の最大値 + 1 を設定して作成 → 一覧へリダイレクト。画面側は成功時に「カテゴリを追加しました」をトースト表示する。

**削除（`destroy()`）:**
1. そのカテゴリに紐づく商品が1件以上ある場合は削除せず、`delete` キーに「このカテゴリには商品が登録されているため削除できません。」を入れて直前の画面へ戻す。
2. 0件なら削除し、登録されていた画像ファイルも削除して一覧へリダイレクトする。画面側は成功時に「カテゴリを削除しました」をトースト表示する。

## 業務ルール

- カテゴリ名の変更は実装しない（追加・削除のみ）。並び替えのUIも設けず、`sort_order` は追加順に採番する。
- 商品が紐づくカテゴリは削除できない。注文明細はカテゴリ名をスナップショットで保持するため過去の注文は壊れないが、公開中の商品がカテゴリを失う状態を作らない。

## 関連ドキュメント

- [docs/admin/common-layout.md](common-layout.md) — 管理画面共通レイアウトと `MasterListCard`・`ConfirmDialog` の正本
- [docs/admin/spec-option.md](spec-option.md) — 同じ `MasterListCard` を使う規格管理
- [docs/admin/banner.md](banner.md) — 画像の条件の定義（`ImageUploadRules`・`IMAGE_ACCEPT`）とプレビュー用フック `useFilePreview` の正本
- [docs/front/top.md](../front/top.md) — カテゴリ画像のフロントでの表示規則の正本
- [docs/2_database.md](../2_database.md) — `categories`・`products` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/CategoryController.php` |
| Controller | `app/Http/Controllers/Admin/CategoryImageController.php` |
| FormRequest | `app/Http/Requests/Admin/Category/StoreCategoryRequest.php` |
| FormRequest | `app/Http/Requests/Admin/Category/UpdateCategoryImageRequest.php` |
| Service | `app/Services/Admin/Category/CategoryImageService.php` |
| Model | `app/Models/Category.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Category/Index.tsx` |
| Component | `resources/js/admin/Components/Category/CategoryImageModal.tsx` |
| Test | `tests/Feature/Admin/Category/CategoryIndexTest.php` |
| Test | `tests/Feature/Admin/Category/CategoryStoreTest.php` |
| Test | `tests/Feature/Admin/Category/CategoryDestroyTest.php` |
| Test | `tests/Feature/Admin/Category/CategoryImageTest.php` |

## 受け入れ条件

- 一覧に登録商品件数が正しく表示される: `tests/Feature/Admin/Category/CategoryIndexTest.php`（`カテゴリ管理画面に登録商品件数付きの一覧が表示される`）
- 一覧が `sort_order` の昇順で並ぶ: 同上（`一覧は表示順の昇順で並ぶ`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- カテゴリを追加できる: `tests/Feature/Admin/Category/CategoryStoreTest.php`（`カテゴリを追加できる`）
- 追加時の `sort_order` が既存の最大値 + 1 になる: 同上（`追加したカテゴリの表示順は既存の最大値の次になる`）
- 同名・未入力・50文字超は追加できない: 同上（`同名のカテゴリは追加できない`・`カテゴリ名が未入力では追加できない`・`カテゴリ名が50文字を超えると追加できない`）
- 未認証は追加できない: 同上（`未認証はカテゴリを追加できない`）
- 商品0件のカテゴリは削除できる: `tests/Feature/Admin/Category/CategoryDestroyTest.php`（`商品が登録されていないカテゴリは削除できる`）
- 商品が紐づくカテゴリは削除できずエラーになる: 同上（`商品が登録されているカテゴリは削除できずエラーになる`）
- 未認証は削除できない: 同上（`未認証はカテゴリを削除できない`）
- 削除すると画像ファイルも消える: 同上（`カテゴリを削除すると画像ファイルも削除される`）
- 一覧に画像のURLが付く: `tests/Feature/Admin/Category/CategoryIndexTest.php`（`画像を登録したカテゴリには画像のアドレスが付く`）
- 画像の登録・差し替え（旧ファイルの削除）・削除ができ、未選択・形式外は拒否され、未認証は操作できない: `tests/Feature/Admin/Category/CategoryImageTest.php`（`カテゴリに画像を登録できる`・`画像を差し替えると元の画像ファイルが削除される`・`カテゴリの画像を削除できる`・`画像が未選択では登録できない`・`許可されていない形式の画像は登録できない`・`未認証はカテゴリの画像を変更できない`）
- 画像モーダルのプレビュー・サムネイル表示・トースト: 自動テストなし。目視確認で担保する
- 削除確認モーダルの表示・キャンセル・`Escape`キーでの閉じる動作: 自動テストなし。目視確認で担保する
- 追加・削除成功時のトースト表示: 自動テストなし。目視確認で担保する
