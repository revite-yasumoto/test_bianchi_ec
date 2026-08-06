# 単位09: 管理画面 - CSVインポート／エクスポート

依存: 単位05, 単位08

商品・会員・管理者マスタのCSV取り込みと書き出し。素の PHP（`fgetcsv` / `fputcsv`）で実装し、CSVライブラリは導入しない。

## スコープ

- 商品CSV登録画面（サイドメニュー「商品管理 > 商品CSV登録」）: ドラッグ＆ドロップ・フォーマット説明・インポート実行・テンプレートDL・エクスポート
- 会員マスタ・管理者マスタのヘッダーにCSVインポート／エクスポートボタンを設置
- 3種のマスタで共通のインポート／エクスポート基盤を作る

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Service） | `laravel-set:laravel` |
| 一括 upsert・トランザクション | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/products/csv` | `admin.products.csv.index` | 商品CSV登録画面 |
| POST | `/admin/products/csv/import` | `admin.products.csv.import` | 商品CSVインポート |
| GET | `/admin/products/csv/export` | `admin.products.csv.export` | 商品CSVエクスポート |
| GET | `/admin/products/csv/template` | `admin.products.csv.template` | 商品CSVテンプレートDL |
| POST | `/admin/members/csv/import` | `admin.members.csv.import` | 会員CSVインポート |
| GET | `/admin/members/csv/export` | `admin.members.csv.export` | 会員CSVエクスポート |
| POST | `/admin/admins/csv/import` | `admin.admins.csv.import` | 管理者CSVインポート |
| GET | `/admin/admins/csv/export` | `admin.admins.csv.export` | 管理者CSVエクスポート |

`/admin/products/csv` は `/admin/products/{product}` より**先に**定義する（`csv` が `{product}` として解釈されるのを防ぐ）。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/ProductCsvController.php` | `index()` / `import()` / `export()` / `template()` |
| Controller | `app/Http/Controllers/Admin/MemberCsvController.php` | `import()` / `export()` |
| Controller | `app/Http/Controllers/Admin/AdminUserCsvController.php` | `import()` / `export()` |
| FormRequest | `app/Http/Requests/Admin/Csv/ImportCsvRequest.php` | ファイルの必須・拡張子・サイズ |
| Service | `app/Services/Admin/Csv/CsvReader.php` | `fopen` + `fgetcsv` で行を読み、文字コードを UTF-8 に変換して連想配列で返す |
| Service | `app/Services/Admin/Csv/CsvWriter.php` | `StreamedResponse` + `fputcsv` で書き出す。BOM 付与・改行コードを制御 |
| Service | `app/Services/Admin/Csv/ProductCsvImporter.php` | 商品CSVの行バリデーションと upsert |
| Service | `app/Services/Admin/Csv/ProductCsvExporter.php` | 商品CSVの列組み立て |
| Service | `app/Services/Admin/Csv/MemberCsvImporter.php` | 会員CSVの行バリデーションと upsert |
| Service | `app/Services/Admin/Csv/MemberCsvExporter.php` | 会員CSVの列組み立て |
| Service | `app/Services/Admin/Csv/AdminUserCsvImporter.php` | 管理者CSVの行バリデーションと upsert |
| Service | `app/Services/Admin/Csv/AdminUserCsvExporter.php` | 管理者CSVの列組み立て |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Product/Csv.tsx` | 左にドロップゾーン＋インポート実行／テンプレートDL、右にフォーマット表＋エクスポート |
| Component | `resources/js/admin/Components/Csv/DropZone.tsx` | ドラッグ＆ドロップ＋ファイル選択。選択後にファイル名を表示 |
| Component | `resources/js/admin/Components/Csv/ImportResultPanel.tsx` | インポート結果（成功件数・スキップ件数・行番号付きエラー一覧）の表示 |
| Component | `resources/js/admin/Components/Csv/CsvActions.tsx` | 一覧ヘッダーに置く「CSVエクスポート」「CSVインポート」ボタンの対 |
| Page | `resources/js/admin/Pages/Member/Index.tsx` | **修正**: ヘッダーに `CsvActions` を追加 |
| Page | `resources/js/admin/Pages/AdminUser/Index.tsx` | **修正**: ヘッダーに `CsvActions` を追加 |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「商品CSV登録」のリンクを有効化 |

## CSVフォーマット

### 商品CSV（要件の8列）

| 列 | 項目 | 必須 | 内容 |
|---|---|---|---|
| A | 商品ID | 必須 | `products.product_code`。既存に一致すれば更新、なければ新規作成 |
| B | 商品名 | 必須 | |
| C | カテゴリ | 必須 | カテゴリ名。`categories` に存在しない名称はエラー |
| D | 価格（税込） | 必須 | 整数 |
| E | SKU有無 | 必須 | `あり` / `なし` |
| F | 枝番 | 条件付 | SKU有無が `あり` のとき必須 |
| G | 在庫数 | 条件付 | SKU有無が `あり` のとき必須（バリエーション単位）。`なし` のときも在庫数として使う |
| H | 公開状態 | 任意 | `公開` / `非公開`。省略時は `非公開` |

SKUあり商品は同一の商品IDで複数行を並べ、行ごとに枝番と在庫数を指定する。サイズ・カラーの列は要件の8列に含まれないため、**CSVからはサイズ・カラーを設定できない**（枝番と在庫のみ）。既存バリエーションの枝番に一致すれば在庫を更新し、一致しなければサイズ・カラーが `null` のバリエーションとして作成する。

### 会員CSV

| 列 | 項目 | 必須 |
|---|---|---|
| A | 会員ID | 任意（省略時は採番） |
| B | 氏名 | 必須 |
| C | 氏名カナ | 任意 |
| D | メールアドレス | 必須 |
| E | 電話番号 | 任意 |
| F | ステータス | 任意（`有効` / `休会`。省略時は `有効`） |
| G | 初期パスワード | 新規作成時のみ必須 |

### 管理者CSV

| 列 | 項目 | 必須 |
|---|---|---|
| A | 管理者ID | 任意（省略時は採番） |
| B | 氏名 | 必須 |
| C | メールアドレス | 必須 |
| D | 初期パスワード | 新規作成時のみ必須 |

## インターフェース ＆ データロジック

### バリデーション

`ImportCsvRequest`:

| 項目 | ルール |
|---|---|
| `file` | 必須・ファイル・拡張子 `csv` または `txt`・MIMEタイプ `text/csv` / `text/plain`・最大10MB |

行単位のバリデーションは各 Importer 内で `Validator::make()` を使って行い、エラーを行番号付きで収集する。

### 主要な処理フロー

**インポート**:
1. ファイルのバリデーション
2. 確認モーダル（「既存の商品データが上書きされる場合があります。実行前にエクスポートによるバックアップを推奨します。」）
3. `CsvReader` でファイルを開き、1行目をヘッダーとして読み飛ばす
4. 文字コードを判定して UTF-8 に変換する（`mb_convert_encoding` で `SJIS-win` / `UTF-8` を自動判定）
5. 1行ずつ検証する。**検証エラーがあれば1件も書き込まず、行番号付きのエラー一覧を返す**（部分適用を作らない）
6. 全行が検証を通ったら、1トランザクション内で upsert する
7. 結果（作成件数・更新件数）をフラッシュして画面へ戻す

**エクスポート**: `StreamedResponse` で出力する。メモリに全件を溜めないよう、Eloquent の `cursor()` で1行ずつ書き出す。

**テンプレートDL**: ヘッダー行のみのCSVを返す。

### 文字コード・改行コード

- **エクスポート**: UTF-8 + BOM（`\xEF\xBB\xBF`）を先頭に付与し、改行は `\r\n`。Excel で開いた際に日本語が文字化けしないようにする
- **インポート**: BOM を除去し、`SJIS-win` / `UTF-8` を自動判定して UTF-8 に変換する

## 業務ルール

- インポートは**全行検証 → 全件適用**とし、一部の行だけが適用された状態を作らない
- 商品CSVからサイズ・カラーは設定できない（要件の列定義に含まれないため）。サイズ・カラーの設定は商品登録フォーム（単位05）から行う
- 会員・管理者CSVのパスワード列は新規作成時のみ使う。既存レコードの更新時は無視し、パスワードを変更しない
- エクスポートしたCSVにパスワードハッシュを含めない

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Csv/ProductCsvImportTest.php` | 新規作成／既存の更新／SKUあり商品の複数行取り込み／存在しないカテゴリ名でエラーになること／必須列の欠落／SKUありで枝番が空のときエラーになること／1行でもエラーがあれば1件も書き込まれないこと |
| `tests/Feature/Admin/Csv/ProductCsvExportTest.php` | 全商品が出力されること／BOM が付くこと／改行コードが `\r\n` であること／SKUあり商品がバリエーション数の行に展開されること |
| `tests/Feature/Admin/Csv/MemberCsvImportTest.php` | 新規作成時に会員IDが採番されること／パスワードがハッシュ化されること／既存会員の更新でパスワードが変わらないこと／メール重複でエラーになること |
| `tests/Feature/Admin/Csv/MemberCsvExportTest.php` | パスワードハッシュが出力に含まれないこと |
| `tests/Feature/Admin/Csv/AdminUserCsvImportTest.php` | 新規作成時に管理者IDが採番されること／取り込んだ管理者でログインできること |
| `tests/Feature/Admin/Csv/CsvEncodingTest.php` | Shift_JIS のCSVを取り込めること／UTF-8 のCSVを取り込めること／BOM 付きCSVを取り込めること |
| `tests/Feature/Admin/Csv/ImportValidationTest.php` | 拡張子・サイズのバリデーション／CSV以外のファイルの拒否 |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **部分適用の可否**: 要件に記載なし。推奨=**全行検証 → 全件適用**（部分適用を作らない）。行ごとにスキップして続行したい場合は実行時に指示すること。
2. **既存データの扱い**: 推奨=商品ID／会員ID／管理者IDをキーに **upsert**（存在すれば更新、なければ作成）。CSVに含まれないレコードは削除しない。
3. **商品CSVのサイズ・カラー**: 要件の8列に列がないため設定できない。列を追加してサイズ・カラーも取り込めるようにするかは実行時に判断すること。
4. **CSVのパスワード列**: 会員・管理者CSVに初期パスワード列を設ける。これは平文をファイルに書く運用になるため、推奨=**エクスポートには含めず、インポートの新規作成時のみ受け付ける**。取り込み後はファイルを破棄する運用を仕様書に明記する。
5. **CSVインポートの非同期化**: 実装しない（同期処理）。件数が増えてタイムアウトする場合はキュー化を別単位で検討する。
6. **注文のCSV**: 対象外（要件のCSV対応表に含まれない）。
7. **商品画像のCSV取り込み**: 対象外（要件の列定義に含まれない）。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で `docs/admin/csv.md` を作成する（3マスタのCSVフォーマット・文字コード・インポート方針の**正本**）。`docs/admin/product-index.md`・`docs/admin/member.md`・`docs/admin/admin-user.md` の `## 関連ドキュメント` に本仕様書へのリンクを追加する
- 本計画ファイルを削除し、トラッカーの状態を更新する
