# メインビジュアル管理

## 機能概要

- **対象画面・機能の目的:** TOPのメインビジュアル（スライド）を一覧し、モーダルで作成・編集・削除する。スライドごとにPC用・スマホ用の画像を登録できる。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/banners` | `admin.banners.index` | `auth:admin` |
| POST | `/admin/banners` | `admin.banners.store` | `auth:admin` |
| PUT | `/admin/banners/{banner}` | `admin.banners.update` | `auth:admin` |
| DELETE | `/admin/banners/{banner}` | `admin.banners.destroy` | `auth:admin` |

作成・編集は一覧画面のモーダルで行うため、`create` / `edit` の専用ページを持たない。画像を含むため送信は `multipart/form-data` になり、更新は `_method=put` によるメソッドスプーフィングで送る。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 管理画面でのバナーの一覧・作成・編集・削除と画像の保存。フロントでの表示は [docs/front/top.md](../front/top.md) が正本。

## 使用テーブル

`banners`（画像は `pc_image_path`・`sp_image_path`）と、訴求商品の選択肢に `products` を読む。定義は [docs/2_database.md](../2_database.md) が正本。背景は `App\Enums\BannerBackground` の5色から選ぶ。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+ メインビジュアル管理 -------------------------- [＋ 新規作成] +
+---------------------------------------------------------------+
| 画像   | 小ラベル / 見出し             | 表示順 | 状態  |         |
|--------|-------------------------------|--------|-------|---------|
| [PC]   | 2026 ROAD COLLECTION          |   0    | 公開  |編集 削除|
|        | 軽さは、遠くまで行くための…   |        |       |         |
+---------------------------------------------------------------+
```

エディタモーダル（新規作成・編集で共通）:

```
+ メインビジュアルを作成 -------------------- × +
| 小ラベル [                                  ] |
| 見出し   [                          （2行）] |
| サブ見出し [                                ] |
| 背景色 [ブルー ▼]      表示順 [0   ]         |
| 訴求商品 [指定しない ▼]                       |
| リンク先（訴求商品を指定しないとき）[/products] |
| 公開状態 [公開 ▼]                            |
| PC用画像（横長 21:9）   [ファイルを選択] [削除]|
| スマホ用画像（縦長 4:5）[ファイルを選択] [削除]|
| [               保存する               ]     |
+-----------------------------------------------+
```

- 一覧の画像欄にはPC用画像（なければスマホ用画像）のサムネイルを出し、どちらもなければ背景色の見本を出す（読み上げでは「画像未登録」と伝える）。サムネイル・プレビューの代替テキストは「登録済みの画像」「選択した画像のプレビュー」とする。
- 画像を選ぶとアップロード前にプレビューを表示する。登録済みの画像は「削除」で外せる（保存時に反映）。
- 画像のプレビュー枠は、PC用を幅240px・21:9、スマホ用を幅112px・4:5 で表示し、横にファイル選択欄を並べる。
- 訴求商品・リンク先の欄には「訴求商品を指定するとその商品詳細へ、指定しないときはリンク先へ移動します。どちらもなければ商品一覧へ移動します。」と説明を添える。
- 画像欄には「未登録の画面幅では背景色で表示します。」と説明を添える。
- バリデーションエラーは各項目の下に表示し、モーダルは開いたままにする。
- 削除は確認モーダル（`ConfirmDialog`）を経て実行する。保存・削除の完了後はトースト（「保存しました」「削除しました」）を表示する。
- 0件のときは「メインビジュアルが登録されていません」を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type BannerRow = {
    id: number;
    tag: string;
    title: string;
    subtitle: string | null;
    background: string;          // BannerBackground の区分コード（選択肢の値）
    background_gradient: string; // 一覧の色見本に使う CSS の値（BannerBackground::gradient()）
    product_id: number | null;
    link_url: string | null;
    sort_order: number;
    is_active: boolean;
    state_label: string;         // '公開' / '非公開'
    pc_image_url: string | null;
    sp_image_url: string | null;
};

type Props = {
    banners: BannerRow[];
    backgroundOptions: { value: string; label: string }[];   // value は区分コード、label は 'ブルー' 等
    productOptions: { id: number; label: string }[];   // '品番 商品名'
};
```

- **入力値バリデーションルール（`Admin\Banner\SaveBannerRequest`。作成・更新で共通）:**

| 項目 | ルール |
|---|---|
| `tag` | 必須・文字列・最大50 |
| `title` | 必須・文字列・最大255 |
| `subtitle` | 任意・文字列・最大255 |
| `background` | 必須・`BannerBackground` の値 |
| `product_id` | 任意・整数・`products.id` に存在 |
| `link_url` | 任意・文字列・最大255・`/` で始まり、2文字目が `/`・`\` でなく、制御文字（タブ・改行等）を含まない |
| `sort_order` | 必須・整数・0〜9999 |
| `is_active` | 必須・真偽値 |
| `pc_image` / `sp_image` | 任意・画像・jpg / jpeg / png / webp・5MB以下・最大辺4000px |
| `remove_pc_image` / `remove_sp_image` | 任意・真偽値 |

画像の条件は商品画像・カテゴリ画像と共通で、本書が正本の `App\Support\ImageUploadRules` に定義する（許可形式・容量・最大辺の値と、商品画像のエラーメッセージに使う表記もここから組み立てる）。管理画面のファイル選択欄が受け付ける形式は `resources/js/admin/lib/imageUpload.ts` の `IMAGE_ACCEPT` に定義し、同じ形式に揃える。

`link_url` の条件は、ブラウザが外部ホストとして解釈する値（`//example.com`・`/\example.com`、解析前にタブ・改行が取り除かれて `//` になる `/\t/example.com`）を弾くためのもの。フロントの遷移先の判定（[docs/front/top.md](../front/top.md)）も同じ条件にそろえる。

- **主要な処理フロー:**

**一覧（`index()`）:** `banners` を `sort_order` 昇順 → `id` 昇順で全件取得する（ページングしない）。画像は `asset('storage/<パス>')` のURLにして渡す。訴求商品の選択肢は全商品を `product_code` 昇順で渡す。背景の選択肢は `BannerBackground` の全ケースを区分コードと表示名で渡す。

公開状態（`is_active`）と画像の削除指定（`remove_*`）は、`multipart/form-data` で `'1'` / `'0'` の文字列として届くため、`BannerSaveService` が `boolean` ルールと同じ解釈（`FILTER_VALIDATE_BOOLEAN`）で読む。

**作成（`store()`）:** バリデーション → `BannerSaveService::create()` が画像を `public` ディスクの `banners/` に保存し、そのパスとともにレコードを作成する → 直前の画面へ戻る。

**更新（`update()`）:** バリデーション → `BannerSaveService::update()` がPC用・スマホ用それぞれについて次を行う。
1. 新しい画像が送られた場合は保存してパスを差し替える。
2. 新しい画像がなく削除が指定された場合はパスを `null` にする。
3. どちらでもなければ既存のパスを保つ。

レコードを更新した後、差し替え・削除で使われなくなった旧ファイルをストレージから削除する。

**削除（`destroy()`）:** `BannerSaveService::delete()` がレコードを削除した後、画像ファイルを削除する。

## 業務ルール

- メインビジュアル管理は要件定義の管理メニュー（[docs/ec_demo_site_requirements.md](../ec_demo_site_requirements.md)）に含まれない追加のメニューで、サイドメニューのトップレベルに置く。
- アップロードした画像のリサイズ・形式変換は行わない。

## 関連ドキュメント

- [docs/front/top.md](../front/top.md) — バナーと画像のフロントでの表示規則の正本
- [docs/admin/common-layout.md](common-layout.md) — 管理画面共通レイアウト・サイドメニュー・`ConfirmDialog`・`DataTable` の正本
- [docs/admin/product-form.md](product-form.md) — 同じ画像のバリデーション定義を使う商品画像のアップロード
- [docs/2_database.md](../2_database.md) — `banners` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/BannerController.php` |
| FormRequest | `app/Http/Requests/Admin/Banner/SaveBannerRequest.php` |
| Service | `app/Services/Admin/Banner/BannerSaveService.php` |
| Enum | `app/Enums/BannerBackground.php` |
| ルール定義 | `app/Support/ImageUploadRules.php` |
| Model | `app/Models/Banner.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Banner/Index.tsx` |
| Component | `resources/js/admin/Components/Banner/BannerManager.tsx` |
| Component | `resources/js/admin/Components/Banner/BannerEditorModal.tsx` |
| Component | `resources/js/admin/Components/Banner/BannerImageField.tsx` |
| Component | `resources/js/admin/Components/Banner/BannerLinkFields.tsx` |
| Hook | `resources/js/admin/hooks/useFilePreview.ts` |
| 定数 | `resources/js/admin/lib/imageUpload.ts` |
| Test | `tests/Feature/Admin/Banner/BannerIndexTest.php` |
| Test | `tests/Feature/Admin/Banner/BannerStoreTest.php` |
| Test | `tests/Feature/Admin/Banner/BannerUpdateTest.php` |
| Test | `tests/Feature/Admin/Banner/BannerDestroyTest.php` |
| Test | `tests/Concerns/FakesImageUpload.php` |

## 受け入れ条件

- 一覧が表示順で並び、画像URL・選択肢が渡る: `tests/Feature/Admin/Banner/BannerIndexTest.php`（`メインビジュアル管理画面に表示順で一覧が表示される`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 画像付き・画像なしで作成でき、背景が区分コードで保存される: `tests/Feature/Admin/Banner/BannerStoreTest.php`（`画像付きでバナーを作成できる`・`画像なしでバナーを作成できる`）
- 必須未入力・背景の選択肢外・サイト外のリンク先（`https://`・`//`・`/\`・タブや改行を挟む値・`javascript:`）・形式外の画像・表示順の範囲外は作成できない: 同上（`必須項目が未入力では作成できない`・`選択肢にない背景は指定できない`・`サイト外へのリンク先は指定できない`・`許可されていない形式の画像は登録できない`・`表示順が範囲外では作成できない`）
- 未認証は作成できない: 同上（`未認証はバナーを作成できない`）
- 更新・画像の差し替え・削除指定・画像の維持: `tests/Feature/Admin/Banner/BannerUpdateTest.php`（`バナーを更新できる`・`画像を差し替えると元の画像ファイルが削除される`・`画像の削除を指定すると画像ファイルとパスが削除される`・`画像を指定しなければ既存の画像が残る`）
- 更新でも入力の検証が働く（代表1ケース）: 同上（`入力が不正なら更新されない`）
- 未認証は更新できない: 同上（`未認証はバナーを更新できない`）
- 削除で画像ファイルも消える・未認証は削除できない: `tests/Feature/Admin/Banner/BannerDestroyTest.php`（`バナーを削除すると画像ファイルも削除される`・`未認証はバナーを削除できない`）
- 画像のプレビュー・削除ボタン・モーダルとトーストの表示: 自動テストなし。目視確認で担保する
