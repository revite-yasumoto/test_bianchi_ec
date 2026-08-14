# 規格管理

## 機能概要

- **対象画面・機能の目的:** SKUバリエーションのサイズ・カラーの一覧・追加・削除。登録した規格値は商品登録のバリエーション設定でワンクリック選択できる選択肢になる。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/spec-options` | `admin.spec-options.index` | `auth:admin` |
| POST | `/admin/spec-options` | `admin.spec-options.store` | `auth:admin` |
| DELETE | `/admin/spec-options/{specOption}` | `admin.spec-options.destroy` | `auth:admin` |

`Route::resource()` の既定のパラメータ名 `spec_option` はコントローラのメソッド引数 `$specOption` とバインドされないため、`parameters()` で `specOption` を明示する。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 規格値の一覧・追加・削除。名称の変更・並び替えのUIは実装しない。画面が使う共通レイアウトと `MasterListCard` は [docs/admin/common-layout.md](common-layout.md) が正本。

## 使用テーブル

`spec_options` テーブルを使用する。定義は [docs/2_database.md](../2_database.md) が正本。`(type, name)` に一意制約を持つ。

商品のバリエーション（`product_variants`）は規格値を `size_name`・`color_name` の**文字列**として保持しており、`spec_options` への外部キーを持たない。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
2カード横並び（grid auto-fit / minmax 280px。幅が足りなければ縦積み）

+---------------------------+  +---------------------------+
| サイズ                     |  | カラー                     |
| 商品登録のバリエーション    |  | 登録済みのカラーはSKUの     |
| 設定でワンクリック選択…     |  | 組み合わせ生成に使用…       |
|---------------------------|  |---------------------------|
| S                    削除 |  | ネイビー              削除 |
| M                    削除 |  | アクア                削除 |
| L                    削除 |  |                           |
|---------------------------|  |---------------------------|
| [例：XXL    ] [ 追加 ]    |  | [例：ネイビー] [ 追加 ]    |
+---------------------------+  +---------------------------+
```

- 追加時のバリデーションエラーは各カードの追加フォーム直下にインラインで表示する。
- 削除は確認モーダル（`ConfirmDialog`）を経てから実行し、モーダル本文で「既に登録済みの商品のSKUには影響しません。」を明示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type SpecOptionRow = { id: number; name: string };
type Props = { sizes: SpecOptionRow[]; colors: SpecOptionRow[] };
```

種別の値は `resources/js/shared/lib/enums.ts` の `SpecOptionType`（`size` / `color`）を使い、バックエンドの `App\Enums\SpecOptionType` と揃える。

- **入力値バリデーションルール（`Admin\SpecOption\StoreSpecOptionRequest`）:**

| 項目 | ルール |
|---|---|
| `type` | 必須・`App\Enums\SpecOptionType` の値（`size` / `color`）のいずれか |
| `name` | 必須・文字列・最大50・同一 `type` 内で一意 |

- **主要な処理フロー:**

**一覧（`index()`）:** `spec_options` を `sort_order` 昇順 → `id` 昇順で1回のクエリで取得し、`type` で `sizes` / `colors` に振り分けて `id`・`name` のみを渡す。

**追加（`store()`）:** バリデーション → `sort_order` に**同一種別内の**最大値 + 1 を設定して作成 → 一覧へリダイレクト。画面側は成功時に「サイズを追加しました」「カラーを追加しました」をトースト表示する。

**削除（`destroy()`）:** 対象を削除して一覧へリダイレクトする。画面側は成功時にトースト表示する。

## 業務ルール

- 規格値の変更は実装しない（追加・削除のみ）。並び替えのUIも設けず、`sort_order` は種別ごとに追加順で採番する。
- 規格値の削除は、その値を使っている商品のバリエーションがあっても許可する。`product_variants` は規格値を文字列として保持しているため既存商品のSKUは壊れず、商品登録画面のワンクリック追加の選択肢から消えるだけである。
- 同じ名称でも種別が異なれば登録できる（一意制約は `(type, name)` の組み合わせ）。

## 関連ドキュメント

- [docs/admin/common-layout.md](common-layout.md) — 管理画面共通レイアウトと `MasterListCard`・`ConfirmDialog` の正本
- [docs/admin/category.md](category.md) — 同じ `MasterListCard` を使うカテゴリ管理
- [docs/2_database.md](../2_database.md) — `spec_options`・`product_variants` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/SpecOptionController.php` |
| FormRequest | `app/Http/Requests/Admin/SpecOption/StoreSpecOptionRequest.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/SpecOption/Index.tsx` |
| 区分値 | `resources/js/shared/lib/enums.ts` |
| Test | `tests/Feature/Admin/SpecOption/SpecOptionIndexTest.php` |
| Test | `tests/Feature/Admin/SpecOption/SpecOptionStoreTest.php` |
| Test | `tests/Feature/Admin/SpecOption/SpecOptionDestroyTest.php` |

## 受け入れ条件

- 一覧がサイズ・カラーの種別ごとに分かれて表示される: `tests/Feature/Admin/SpecOption/SpecOptionIndexTest.php`（`規格管理画面にサイズとカラーが種別ごとに分かれて表示される`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- サイズ・カラーを追加できる: `tests/Feature/Admin/SpecOption/SpecOptionStoreTest.php`（`サイズを追加できる`・`カラーを追加できる`）
- 追加時の `sort_order` が同一種別内の最大値 + 1 になる: 同上（`表示順は同じ種別の最大値の次になる`）
- 同一種別の同名は追加できず、種別が異なれば同名でも追加できる: 同上（`同じ種別で同名の規格は追加できない`・`種別が違えば同名の規格を追加できる`）
- 不正な種別・50文字超は追加できない: 同上（`種別が不正な値では追加できない`・`規格値が50文字を超えると追加できない`）
- 未認証は追加できない: 同上（`未認証は規格を追加できない`）
- 規格を削除できる: `tests/Feature/Admin/SpecOption/SpecOptionDestroyTest.php`（`規格を削除できる`）
- 削除しても同じ規格値を使う商品のバリエーションが残る: 同上（`規格を削除しても同じ規格値を使う商品のバリエーションは残る`）
- 未認証は削除できない: 同上（`未認証は規格を削除できない`）
- 2カードのレスポンシブ表示（幅が足りない場合は縦積み）: 自動テストなし。目視確認で担保する
- 削除確認モーダルの表示・注意文・キャンセル動作: 自動テストなし。目視確認で担保する
