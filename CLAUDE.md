# プロジェクトルール

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | Laravel |
| ブリッジ | Inertia.js |
| フロントエンド | React |
| 言語（フロントエンド） | TypeScript（`.tsx`/`.ts`、`any`禁止） |
| UIライブラリ | Tailwind CSS |

## ディレクトリ設計

一般ユーザー向け画面と管理者向け管理画面を分離して実装する。

- 一般ユーザー向け画面は `front`、管理者向け画面は `admin` に配置する。
- 画面固有の `Pages`、`Layouts`、`Components` は `front` / `admin` 以下に分ける。
- 両画面で再利用する小さなUIパーツやユーティリティは `shared` または `lib` に置く。
- Laravel Controller は `App\Http\Controllers\Front` / `App\Http\Controllers\Admin` に分ける。
- 管理画面の route 名は必ず `admin.` prefix を付ける。
- Inertia のページ名はディレクトリを含めて指定する（例: `Inertia::render('front/Top')`）。

## ワークスペース操作ルール

### 許可された操作（承認不要）

- 現在開いているプロジェクトディレクトリ内のファイルの読み取り・編集

### 禁止された操作

- プロジェクトディレクトリ外のファイル・フォルダへのアクセス・編集

### ユーザー承認が必要な操作

以下の操作は**必ずユーザーに確認を求め、明示的な承認を得てから実行**:

- システム設定の変更（`php.ini`、`nginx.conf` 等）
- グローバルパッケージのインストール・変更
- DBスキーマ・データを変更するコマンド（下記参照）

### DBコマンドは自動実行禁止

以下のコマンドはユーザーの明示的な承認なしに絶対実行しない:

```
php artisan migrate
php artisan migrate:fresh
php artisan db:seed
php artisan migrate:rollback
```

実行が必要な場合は、コマンドを提示してユーザーに確認を求め、承認を得ること。

## セキュリティルール

- シークレットキー（APIキー、パスワード等）は必ず `.env` で管理し、`config/` 経由で参照する。コードにハードコードしない。
- `HandleInertiaRequests.php` のSharedDataに、バックエンドの機密情報を含めない。フロントエンドへ渡すデータは公開しても安全な情報のみとする。

## 仕様書（docs/*.md）とソースコードの同期ルール

本プロジェクトでは `docs/` 配下の `.md` ファイル（仕様書）がソースコードの正確な記述であることを維持する。

### 原則: md = コード

- 仕様書は「過去の設計意図」ではなく「現在のコードの正確な反映」であるべき。
- コードを変更したら、対応するmdファイルも同時に更新する。
- 「注記」や「→ その後 xxx で変更」のような追記ではなく、セクション自体を最新の状態に書き換える。

### コード変更時の必須アクション

コードを修正・追加・削除した場合、以下を必ず行うこと：

1. **直接対応するmdの更新:** 変更したファイルが `## ソースファイル` セクションに記載されているmdファイルを特定し、該当セクションをコードに合わせて更新する。
2. **関連mdの波及確認:** 更新したmdの `## 関連ドキュメント` に記載されている他のmdファイルを確認し、波及的な乖離があればそちらも更新する。
3. **ソースファイルセクションの更新:** 新しいファイルを追加した場合やパスが変わった場合は `## ソースファイル` も更新する。

### 判断基準

- コードとmdが矛盾する場合、**コード（実装）を正**とする。
- mdに書かれていてコードに存在しない機能は、実装漏れか仕様変更のいずれか。ユーザーに確認する。
- コードに存在してmdに書かれていない機能は、md更新漏れ。mdを更新する。

## 変更規模の判定基準

`implement` の仕様確定方法、`commit` の同期・チェック深度、Planモードへの遷移要否など、規模に応じて処理を分岐する箇所は、以下の基準に従う（各スキル・ルールに重複記載しない）。

以下のいずれか1つでも該当すれば「大規模」、いずれにも該当しなければ「小規模」とする。判定に迷う場合は大規模側に倒す。

- 変更ファイル数が3つ以上（実装前は見積もり、実装後は `git diff` 実測）
- 新規テーブル・カラムの追加（`database/migrations/` 配下への新規ファイル）
- 破壊的変更（メソッドシグネチャ・ルート・レスポンス形状の変更）
- 新機能・新画面の追加（新規Controllerクラス、またはフロントエンドの新規ページファイル追加）
- 追加+削除行数がおおよそ50行を超える
- **危険シグナル**を含む（`Gate::` / `->authorize(` / `Policy` / `$fillable` / `$guarded` / `FormRequest` / `dangerouslySetInnerHTML` / `{!!` / `encrypted` / `$hidden` / `DB::raw` / `whereRaw` / `.env` / `routes/*.php` への変更・新規追加）— 該当すれば行数・ファイル数に関わらず無条件で大規模とする。1行の変更でも認可漏れ・XSS・SQLインジェクション等に直結しうるため、「小さい差分だから軽い」という前提がちょうど崩れる典型パターンである

## Planモードの活用

- **複雑・大規模タスクはPlanモードから開始**: 上記「変更規模の判定基準」で大規模と判定される場合は、実装前にAIが `EnterPlanMode` ツールを能動的に呼び出してPlanモードに入り、計画を提示しユーザーの確認を取る。ユーザーが `/plan` を明示的に起動した場合はそれに従う。
- **Planモード中はコードを変更しない**: 計画の確認を得てからPlanモードを抜けて実装に入る。

## スキルの強制適用

コードを実装・修正する際、対象ファイルに応じて以下のスキルを必ず呼び出すこと：

| 対象ファイル / タイミング | 呼び出すスキル | スキルファイル |
|---|---|---|
| `.php`（Laravelファイル） | `laravel` | [.claude/skills/laravel/SKILL.md](.claude/skills/laravel/SKILL.md) |
| `.tsx` / `.ts`（Reactファイル） | `react` | [.claude/skills/react/SKILL.md](.claude/skills/react/SKILL.md) |
| 外部API連携・非同期通信 | `api-integration` | [.claude/skills/api-integration/SKILL.md](.claude/skills/api-integration/SKILL.md) |
| 実装完了後（共通） | `code-styling` | [.claude/skills/code-styling/SKILL.md](.claude/skills/code-styling/SKILL.md) |
| バックエンドテスト | `testing-backend` | [.claude/skills/testing-backend/SKILL.md](.claude/skills/testing-backend/SKILL.md) |
| E2Eテスト（判定基準は `implement` スキル参照） | `testing-e2e` | [.claude/skills/testing-e2e/SKILL.md](.claude/skills/testing-e2e/SKILL.md) |
| 画面・機能定義書の作成 | `specification-writer` | [.claude/skills/specification-writer/SKILL.md](.claude/skills/specification-writer/SKILL.md) |

スキルはコード変更の**前または最中**に呼び、チェックリストを適用してから完了を報告する。

## implementスキルの使い方

`implement` スキルは、上記「スキルの強制適用」表の*自動適用*スキルとは異なり、**`commit` と同様にユーザーが起動するスキル**である。実装着手時にユーザーが `/implement` を打って起動する。起動後は、規模判定 → 仕様確定 → Plan mode承認 → 実装（`react`/`laravel` 等を適用） → テスト → `code-styling` → 仕様書同期、の順で各スキルを巻いて進める。

## design-to-codeスキルの使い方

`design-to-code` スキルは **Claude Design のデザインファイルをもとにコードを生成する**ときにユーザーが起動するスキルである。Claude Design の URL と仕様書（`docs/*.md`）を組み合わせ、視覚仕様とデータ仕様の両方を参照しながら精度の高い実装を行う。

1. ユーザーが Claude Design の URL を共有し、`/design-to-code` を起動する
2. スキルが HTML からデザイントークン・レイアウト・コンポーネント仕様を抽出する
3. 対応する仕様書（`.md`）のデータ仕様と組み合わせて実装する
4. 実装完了後に `specification-writer` の同期モードで仕様書を更新する

## spec-auditスキルの使い方

`spec-audit` スキルは **仕様書・ソースコード・テストコードの整合性を一括監査する**ときにユーザーが起動するスキルである。定期的なチェックや実装後の品質確認に使う。

1. ユーザーが `/spec-audit` を起動する
2. スキルが「仕様書 ↔ ソースコード差分」「テストコードの過不足」「仕様書同士の整合性」の3観点でチェックする
3. 優先度付きの対応一覧をレポートとして出力する

## vuln-auditスキルの使い方

`vuln-audit` スキルは **外部の脆弱性診断を受ける前、または納品前・リリース前の最終セキュリティチェックを行う**ときにユーザーが起動するスキルである。「スキルの強制適用」表の自動適用スキルとは異なり、実装中には呼び出さない。

1. ユーザーが `/vuln-audit` を起動する
2. Step 0 で監査対象・範囲・認証区分・公開形態・PHPネイティブセッション管理の要否・`.env` の確認方法をユーザーに確認する
3. 自動チェック（`composer audit` 等）と V1〜V9（OWASP Top 10 / IPA 基準）の照合を行う
4. 優先度付きレポートを出力し、ユーザーが承認した項目のみ Step 5 で修正を適用する

具体例集として `examples.md`（NG/OKコード例・推奨パッケージ）を併置している。

## delivery-checkスキルの使い方

`delivery-check` スキルは **納品前の最終チェックを一括実施する**ときにユーザーが起動するスキルである。AIが自動で呼び出してはならない。

1. ユーザーが `/delivery-check`（公開URLがあれば `/delivery-check https://example.com`）を起動する
2. スキルがプロジェクト種別を判定し、meta/OGP・パフォーマンス（PSI対策）・リダイレクト・セキュリティ残置物をコードから自動チェックする
3. GTM/GA・Search Console・DNS・メール認証などアカウント作業を伴う項目は「手動チェックリスト」としてレポート末尾に出力する
4. レポート提示後、修正はユーザーの指示を受けてから行う

深いセキュリティ監査が必要な場合は `vuln-audit` を併用する（`delivery-check` はデバッグ出力・ハードコード等の表層チェックのみ）。

## webp-convertスキルの使い方

`webp-convert` スキルは **PNG/JPEG画像をWebPへ一括変換する**ときにユーザーが起動するスキルである。AIが自動で呼び出してはならない。

1. ユーザーが `/webp-convert`（対象ディレクトリ・品質を引数で指定可）を起動する
2. スキルが変換ツール（`cwebp` / ImageMagick / `npx sharp-cli`）を検出し、対象画像の一覧を提示して承認を得てから変換する
3. サイズ削減率のレポートを出力する。コード内参照の書き換えは承認を得てから行う

`og:image`・faviconは変換対象外。`delivery-check` のWebP項目で残存が報告された場合の是正に使う。

## commitスキルの使い方

`commit` スキルは **AIが自動で呼び出すものではない**。以下の流れで使う：

1. AIが実装を完了し、`code-styling` スキルまで終えた状態で報告する
2. **ユーザーが動作確認・必要なら修正指示** を行う
3. 問題なければユーザーが `/commit` を打って起動する

AIは実装完了後に「`/commit` を実行してください」と案内するだけにとどめ、勝手にコミットしない。
