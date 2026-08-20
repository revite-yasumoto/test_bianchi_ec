# CSVインポート・エクスポート

本ドキュメントは商品・会員・管理者マスタの**CSVフォーマット**と**インポートの適用方針**、および管理画面が出力する全CSVに共通する**文字コード・改行コードの扱い**の正本。各マスタの仕様書はこれらを再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** 商品・会員・管理者マスタをCSVで一括登録・更新し、また現在のデータをCSVで書き出す。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/products/csv` | `admin.products.csv.index` | `auth:admin` |
| POST | `/admin/products/csv/import` | `admin.products.csv.import` | `auth:admin` |
| GET | `/admin/products/csv/export` | `admin.products.csv.export` | `auth:admin` |
| GET | `/admin/products/csv/template` | `admin.products.csv.template` | `auth:admin` |
| POST | `/admin/members/csv/import` | `admin.members.csv.import` | `auth:admin` |
| GET | `/admin/members/csv/export` | `admin.members.csv.export` | `auth:admin` |
| POST | `/admin/admins/csv/import` | `admin.admins.csv.import` | `auth:admin` |
| GET | `/admin/admins/csv/export` | `admin.admins.csv.export` | `auth:admin` |

CSVのルートは `products/{product}` `members/{user}` `admins/{admin}` として解釈されないよう、各リソースのルートより**先に**定義する。

- **アクセス権限・ミドルウェア:** 上表の通り。
- **本ドキュメントのスコープ:** 3マスタのCSV取り込みと書き出し、および全CSVに共通する文字コード・改行コードの扱い。注文のCSVと商品画像のCSV取り込みは対象外。お問い合わせのCSVエクスポート（列定義・出力範囲）は [docs/admin/contact.md](contact.md) が正本で、文字コード・改行コードのみ本書に従う。

## 使用テーブル

商品CSVは `products` / `product_variants` / `stocks` に書き込み、`categories` をカテゴリ名で参照する。会員CSVは `users`、管理者CSVは `admins` に書き込む。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
商品CSV登録（GET /admin/products/csv） 2カラム
+---------------------------+  +---------------------------+
| CSVインポート              |  | フォーマット               |
| +-----------------------+ |  | 列|項目        |必須      |
| | ここにCSVをドロップ    | |  | A |商品ID      |必須      |
| |      または            | |  | B |商品名      |必須      |
| |   [ファイルを選択]     | |  | … |            |          |
| |   products.csv 取り消す| |  +---------------------------+
| +-----------------------+ |  | CSVエクスポート            |
| [インポート実行][テンプレDL]| |  | [CSVエクスポート]          |
+---------------------------+  +---------------------------+
| インポート結果             |
| 新規 3件 / 更新 1件 …      |
+---------------------------+

会員マスタ・管理者マスタの一覧ヘッダー
[CSVエクスポート] [CSVインポート]  （管理者マスタは加えて [＋ 管理者登録]）
一覧本体の上に「インポート結果」パネルを表示する
```

- インポートはファイル選択後に確認モーダル（`ConfirmDialog`）を経て送信する。本文で既存データの上書きとバックアップの推奨を伝える。
- インポート結果は成功時に「新規N件 / 更新N件」、失敗時に行番号付きのエラー一覧を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
/** 共有プロパティ `flash.importResult` で受け取る。errors があれば1件も適用されていない */
type CsvImportResult = {
    created: number;
    updated: number;
    errors: { line: number; message: string }[];
};
```

インポート結果は `AdminSharedProps` の `flash.importResult` として共有する（型の正本は [docs/admin/auth.md](auth.md)）。

- **入力値バリデーションルール（`Admin\Csv\ImportCsvRequest`）:**

| 項目 | ルール |
|---|---|
| `file` | 必須・ファイル・拡張子 `csv` / `txt`・MIME `text/csv` / `text/plain` / `application/csv` / `application/vnd.ms-excel`・10MB以下 |

CSVのMIMEは出力元によって揺れるため、拡張子と代表的なMIMEの双方を許可する。行単位のバリデーションは各 Importer が `Validator::make()` で行い、エラーを行番号付きで収集する。

### 商品CSV

| 列 | 項目 | 必須 | 内容 |
|---|---|---|---|
| A | 商品ID | 必須 | `products.product_code`。半角英数とハイフン。既存に一致すれば更新、なければ新規作成 |
| B | 商品名 | 必須 | 最大255 |
| C | カテゴリ | 必須 | カテゴリ名。`categories` に存在しない名称はエラー |
| D | 価格（税込） | 必須 | 整数・0以上・9,999,999以下 |
| E | SKU有無 | 必須 | `あり` / `なし` |
| F | 枝番 | 条件付 | SKU有無が `あり` のとき必須 |
| G | 在庫数 | 必須 | 整数・0以上・999,999以下 |
| H | 公開状態 | 任意 | `公開` / `非公開`。省略時は非公開 |

- SKUあり商品は同一の商品IDで複数行を並べ、行ごとに枝番と在庫数を指定する。商品の属性（商品名・カテゴリ・価格・公開状態）は最初の行の値を使う。
- 既存バリエーションの枝番に一致すれば在庫を更新し、一致しなければ新規に作成する。
- **CSVからサイズ・カラーは設定できない**（要件の列定義に含まれないため）。CSVで新規作成したバリエーションはサイズ・カラーが `null` になる。設定は商品登録フォーム（[docs/admin/product-form.md](product-form.md)）から行う。
- SKUなし商品はサイズ・カラーを持たないバリエーション1件に集約し、`sku_code` は商品IDそのものになる。

### 会員CSV

| 列 | 項目 | 必須 | 内容 |
|---|---|---|---|
| A | 会員ID | 任意 | 省略時は採番する（採番規則は [docs/front/auth.md](../front/auth.md) が正本） |
| B | 氏名 | 必須 | 最大100 |
| C | 氏名カナ | 任意 | 最大100 |
| D | メールアドレス | 必須 | メール形式・最大191・既存および同一ファイル内で一意 |
| E | 電話番号 | 任意 | 最大20 |
| F | ステータス | 任意 | `有効` / `休会`。省略時は有効 |
| G | 初期パスワード | 新規時のみ必須 | 8文字以上。既存レコードの更新時は無視する |

既存レコードの特定は、会員IDが指定されていればそれを、無ければメールアドレスで行う。

**退会済みの会員は取込の対象外**とし、その行はエラーとして報告して更新しない。退会は会員の意思による操作で、管理者が戻せない扱いのため（[docs/front/withdrawal.md](../front/withdrawal.md)・[docs/admin/member.md](member.md) が正本）。ステータス列に `退会` を書いても受け付けない。

エクスポートの列は A〜F ＋「登録日」で、**初期パスワードは含めない**。退会済みの会員はステータス列に `退会` を出力する。

### 管理者CSV

| 列 | 項目 | 必須 | 内容 |
|---|---|---|---|
| A | 管理者ID | 任意 | 省略時は採番する（採番規則は [docs/admin/admin-user.md](admin-user.md) が正本） |
| B | 氏名 | 必須 | 最大100 |
| C | メールアドレス | 必須 | メール形式・最大191・既存および同一ファイル内で一意 |
| D | 初期パスワード | 新規時のみ必須 | 8文字以上。既存レコードの更新時は無視する |

エクスポートの列は A〜C ＋「登録日」で、**初期パスワードは含めない**。

### 主要な処理フロー

**インポート:**
1. `ImportCsvRequest` でファイルを検証する。
2. `CsvReader` がファイルを読み、1行目をヘッダーとして読み飛ばす。全列が空の行は読み飛ばす。
3. 各行を検証する。**1行でもエラーがあれば1件も書き込まず**、行番号付きのエラー一覧を返す。行番号はヘッダーを1行目として数える。
4. 全行が検証を通ったら、1トランザクション内で作成または更新する。
5. 結果（新規件数・更新件数）をフラッシュして直前の画面へ戻す。

**エクスポート:** `StreamedResponse` で1行ずつ書き出す。商品は eager load を効かせるため `lazy()` で読む（`cursor()` は eager load が効かない）。会員・管理者は関連を持たないため `cursor()` で読む。

**テンプレートDL:** ヘッダー行のみのCSVを返す。

### 文字コード・改行コード

- **エクスポート**: UTF-8 の先頭に BOM（`\xEF\xBB\xBF`）を付け、改行は CRLF。Excel で開いたときに文字化けしないようにする。`fputcsv` は LF を出力するため、行ごとに CRLF へ置き換える。
- **インポート**: BOM を除去し、UTF-8 と `SJIS-win` を自動判定して UTF-8 に変換する。改行は CRLF / LF のどちらでも読める。

## 業務ルール

- インポートは**全行検証 → 全件適用**とし、一部の行だけが適用された状態を作らない。
- 商品ID・会員ID・管理者IDをキーに作成または更新する。CSVに含まれないレコードは削除しない。
- 会員・管理者CSVの初期パスワード列は新規作成時のみ使う。既存レコードの更新では無視し、パスワードを変更しない。
- 初期パスワードは平文でファイルに書く運用になるため、**取り込み後はCSVファイルを破棄する**。エクスポートには初期パスワード列を含めない。
- インポートは同期処理で行う。件数が増えて実行時間が問題になる場合はキュー化を別途検討する。
- 注文のCSV・商品画像のCSV取り込みは対象外。

## 関連ドキュメント

- [docs/admin/product-form.md](product-form.md) — 商品のバリエーション・サイズ・カラーの設定の正本
- [docs/admin/product-index.md](product-index.md) — 商品一覧
- [docs/admin/member.md](member.md) — 会員マスタ
- [docs/admin/admin-user.md](admin-user.md) — 管理者マスタ。管理者IDの採番規則の正本
- [docs/front/auth.md](../front/auth.md) — 会員IDの採番規則の正本
- [docs/admin/auth.md](auth.md) — `AdminSharedProps` の型の正本
- [docs/admin/contact.md](contact.md) — お問い合わせのCSVエクスポートの正本（`CsvWriter` と本書の文字コード規則を共有する）
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/ProductCsvController.php` |
| Controller | `app/Http/Controllers/Admin/MemberCsvController.php` |
| Controller | `app/Http/Controllers/Admin/AdminUserCsvController.php` |
| FormRequest | `app/Http/Requests/Admin/Csv/ImportCsvRequest.php` |
| Service | `app/Services/Admin/Csv/CsvReader.php` |
| Service | `app/Services/Admin/Csv/CsvWriter.php` |
| Service | `app/Services/Admin/Csv/ImportResult.php` |
| Service | `app/Services/Admin/Csv/ProductCsvImporter.php` |
| Service | `app/Services/Admin/Csv/ProductCsvExporter.php` |
| Service | `app/Services/Admin/Csv/MemberCsvImporter.php` |
| Service | `app/Services/Admin/Csv/MemberCsvExporter.php` |
| Service | `app/Services/Admin/Csv/AdminUserCsvImporter.php` |
| Service | `app/Services/Admin/Csv/AdminUserCsvExporter.php` |
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Product/Csv.tsx` |
| Component | `resources/js/admin/Components/Csv/DropZone.tsx` |
| Component | `resources/js/admin/Components/Csv/ImportResultPanel.tsx` |
| Component | `resources/js/admin/Components/Csv/CsvActions.tsx` |
| 型 | `resources/js/types/global.d.ts` |
| Test | `tests/Feature/Admin/Csv/ProductCsvImportTest.php` |
| Test | `tests/Feature/Admin/Csv/ProductCsvExportTest.php` |
| Test | `tests/Feature/Admin/Csv/MemberCsvImportTest.php` |
| Test | `tests/Feature/Admin/Csv/MemberCsvExportTest.php` |
| Test | `tests/Feature/Admin/Csv/AdminUserCsvImportTest.php` |
| Test | `tests/Feature/Admin/Csv/CsvEncodingTest.php` |
| Test | `tests/Feature/Admin/Csv/ImportValidationTest.php` |

## 受け入れ条件

- 商品CSV登録画面が表示される: `tests/Feature/Admin/Csv/ProductCsvImportTest.php`（`商品の取込画面が表示される`）
- 商品を新規登録・更新できる: 同上（`規格なし商品を新規登録できる`・`既存商品を更新できる`）
- SKUあり商品が複数行から取り込まれ、既存の枝番は在庫のみ更新される: 同上（`規格あり商品は同じ商品識別コードの複数行から取り込まれる`・`既存の枝番に一致する行は在庫だけが更新される`）
- 公開状態の省略が非公開になる: 同上（`公開状態を省略すると非公開になる`）
- 存在しないカテゴリ名・枝番欠落・必須列欠落がエラーになる: 同上（`存在しないカテゴリ名はエラーになる`・`規格ありで枝番が空の行はエラーになる`・`必須列が欠けている行はエラーになる`）
- 1行でもエラーがあれば1件も取り込まれない: 同上（`一行でもエラーがあれば1件も取り込まれない`）
- インポート結果が行番号・件数付きで返る: 同上（`インポート結果が行番号付きで返る`・`成功時のインポート結果に件数が入る`）
- 未認証はインポートできない: 同上（`未認証はインポートできない`）
- 商品が書き出され、BOM・CRLF・ヘッダーが付く: `tests/Feature/Admin/Csv/ProductCsvExportTest.php`（`商品が書き出される`・`先頭にバイトオーダーマークが付く`・`改行コードが復帰改行になる`・`ヘッダー行が出力される`）
- SKUあり商品がバリエーションごとの行に展開される: 同上（`規格あり商品はバリエーションごとの行に展開される`）
- テンプレートがヘッダーのみを含む: 同上（`テンプレートはヘッダーだけを含む`）
- 会員を新規登録・更新でき、会員IDが採番される: `tests/Feature/Admin/Csv/MemberCsvImportTest.php`（`会員を新規登録できる`・`会員番号を省略すると採番される`・`会員番号を指定するとその値で登録される`）
- 退会済みの会員は取込で更新されない: 同上（`退会済みの会員は取り込みで更新されない`）
- 初期パスワードがハッシュ化され、更新時は変わらない: 同上（`初期パスワードはハッシュ化して保存される`・`既存会員を更新してもパスワードは変わらない`）
- 新規で初期パスワード欠落・メール重複（既存／ファイル内）・氏名欠落がエラーになる: 同上（`新規登録で初期パスワードが空ならエラーになる`・`他の会員と重複するメールアドレスはエラーになる`・`ファイル内でメールアドレスが重複するとエラーになる`・`氏名が空の行はエラーになる`）
- 会員の書き出しにパスワードハッシュ・記憶トークンが含まれない: `tests/Feature/Admin/Csv/MemberCsvExportTest.php`（`パスワードハッシュは書き出されない`・`記憶トークンは書き出されない`）
- 管理者を新規登録でき、管理者IDが採番され、取り込んだ管理者でログインできる: `tests/Feature/Admin/Csv/AdminUserCsvImportTest.php`（`管理者を新規登録できる`・`管理者番号を省略すると採番される`・`取り込んだ管理者でログインできる`）
- 管理者の更新でパスワードが変わらず、書き出しにハッシュが含まれない: 同上（`既存管理者を更新してもパスワードは変わらない`・`管理者の書き出しにパスワードハッシュが含まれない`）
- UTF-8・BOM付き・Shift_JIS・LF改行のいずれも取り込める: `tests/Feature/Admin/Csv/CsvEncodingTest.php`（`文字コードが自動判定されて取り込める`・`バイトオーダーマーク付きでも取り込める`・`日本語を含む旧来の文字コードでも取り込める`・`改行が復帰改行でなくても取り込める`）
- 空行が読み飛ばされる: 同上（`空行は読み飛ばされる`）
- 拡張子・サイズ・未選択のバリデーション: `tests/Feature/Admin/Csv/ImportValidationTest.php`（`拡張子がテキストのファイルも取り込める`・`ファイルが未選択ならエラーになる`・`許可されていない拡張子は拒否される`・`上限を超えるサイズのファイルは拒否される`）
- ヘッダーだけのファイルが何も取り込まない: 同上（`ヘッダーだけのファイルは何も取り込まない`）
- ドラッグ＆ドロップでのファイル選択: 自動テストなし。目視確認で担保する
- インポート実行の確認モーダルと結果パネルの表示: 自動テストなし。目視確認で担保する
- ダウンロードしたCSVをExcelで開いて文字化けしないこと: 自動テストなし。目視確認で担保する
