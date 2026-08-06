# 単位02: 会員認証＋フロント共通レイアウト

依存: 単位01

フロント側の土台。会員登録・ログイン・ログアウトと、以降の全フロント画面が使う共通レイアウト・共通UIコンポーネント・デザイントークンを整備する。

## スコープ

- 会員の会員登録・ログイン・ログアウト（`web` ガード）
- 購入導線からの遷移時に、ログイン後に元の手続きへ復帰する仕組み（intended URL）
- フロント共通レイアウト（ヘッダー・フッター・SPハンバーガーメニュー・トースト）
- 以降の単位で共通利用するUIコンポーネント
- Tailwind CSS のデザイントークン定義（配色・フォント・角丸）
- カートドロワー・お気に入り件数の**表示枠のみ**（中身のデータ連携は単位13・15）

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Middleware） | `laravel-set:laravel` |
| `.tsx` / `.ts`（Page / Layout / Component） | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定・`app.css` | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## デザイントークン（モックの `_ds/.../site.css` より）

`resources/css/app.css` に Tailwind v4 の `@theme` で定義する。

| トークン | 値 | 用途 |
|---|---|---|
| `--color-bg` | `#FFFFFF` | 基本背景 |
| `--color-bg2` | `#F5F1EA` | セクション背景・サイドカード |
| `--color-ink` | `#232323` | 本文 |
| `--color-ink2` | `#5E6B77` | 補助テキスト |
| `--color-line` | `#E7DFD2` | 罫線・枠線 |
| `--color-brand` | `#2F6F86` | ブランド色（主要ボタン・アクティブ） |
| `--color-brand-deep` | `#274F60` | ロゴ・フッター背景 |
| `--color-amber` | `#E7A93A` | 強調 |
| `--color-coral` | `#E1664B` | 購入導線ボタン・削除・エラー |
| `--color-rose` | `#C25E86` | アクセント |
| `--color-teal` | `#3E9E8F` | 成功・在庫あり |
| `--font-sans` | `Schibsted Grotesk` | ロゴ・英字見出し |
| `--font-jp` | `Zen Kaku Gothic New` | 日本語本文（既定） |
| `--font-mono` | `Space Mono` | 金額・コード・日付 |

ステータス系の文字色／背景色の対（在庫あり `#2b6f64` / `#E4F2EF`、在庫切れ `#8a4030` / `#FBE7E1` 等）は `resources/js/shared/lib/tone.ts` に定数として置き、フロント・管理画面の双方から参照する。

## ルーティング（`routes/web.php`）

| メソッド | パス | ルート名 | 認証 | 内容 |
|---|---|---|---|---|
| GET | `/register` | `register` | guest | 会員登録フォーム |
| POST | `/register` | `register.store` | guest | 会員登録実行 |
| GET | `/login` | `login` | guest | ログインフォーム |
| POST | `/login` | `login.store` | guest | ログイン実行 |
| POST | `/logout` | `logout` | auth | ログアウト |

`admin.` prefix を持たないフロント側のルート名は prefix なしとする。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/Auth/RegisteredUserController.php` | `create()` / `store()`。会員IDを採番し、登録後にログインさせる |
| Controller | `app/Http/Controllers/Front/Auth/AuthenticatedSessionController.php` | `create()` / `store()` / `destroy()`。`store()` はセッション再生成・intended URL への復帰 |
| FormRequest | `app/Http/Requests/Front/Auth/RegisterRequest.php` | 会員登録のバリデーション |
| FormRequest | `app/Http/Requests/Front/Auth/LoginRequest.php` | ログインのバリデーション＋認証試行＋レート制限 |
| Action | `app/Actions/Front/Auth/GenerateMemberCode.php` | 会員ID（`M-` + 6桁連番）の採番 |
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` | **修正**: `auth.user`（id / member_code / name のみ）・`cartCount`・`favoriteCount`・`flash` を共有。機密情報は共有しない |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| CSS | `resources/css/app.css` | **修正**: `@theme` にデザイントークンを定義。Google Fonts を読み込む |
| エントリ | `resources/js/app.tsx` | **修正**: ページ解決を `front/` `admin/` の両ディレクトリに対応させる |
| 型 | `resources/js/types/global.d.ts` | **修正**: `SharedProps`（`auth` / `cartCount` / `favoriteCount` / `flash`）を定義 |
| Layout | `resources/js/front/Layouts/FrontLayout.tsx` | ヘッダー＋フッター＋トーストを含む共通レイアウト |
| Component | `resources/js/front/Components/Header.tsx` | ロゴ・PCナビ・お気に入り件数・カートボタン・SPハンバーガー |
| Component | `resources/js/front/Components/MobileMenu.tsx` | SP時の開閉メニュー |
| Component | `resources/js/front/Components/Footer.tsx` | ブランド＋SHOPPING / SUPPORT / LEGAL の3カラム＋コピーライト |
| Component | `resources/js/front/Components/CartDrawer.tsx` | 右スライドインのカートドロワー（本単位では表示枠のみ） |
| Page | `resources/js/front/Pages/Auth/Register.tsx` | 会員登録フォーム |
| Page | `resources/js/front/Pages/Auth/Login.tsx` | ログインフォーム |
| 共通UI | `resources/js/shared/Components/Button.tsx` | `variant`: `primary`（brand）/ `cta`（coral）/ `outline` / `ghost` |
| 共通UI | `resources/js/shared/Components/TextInput.tsx` | ラベル＋input＋エラー表示（エラーは入力欄の直下） |
| 共通UI | `resources/js/shared/Components/SelectInput.tsx` | ラベル＋select＋エラー表示 |
| 共通UI | `resources/js/shared/Components/TextareaInput.tsx` | ラベル＋textarea＋エラー表示 |
| 共通UI | `resources/js/shared/Components/Checkbox.tsx` | チェックボックス（モックの角丸カスタム表示に合わせる） |
| 共通UI | `resources/js/shared/Components/Badge.tsx` | ステータスバッジ（文字色／背景色の対を受け取る） |
| 共通UI | `resources/js/shared/Components/Modal.tsx` | 中央表示のモーダル（背景クリックで閉じる） |
| 共通UI | `resources/js/shared/Components/Toast.tsx` | 画面下中央のトースト。`flash` を監視して自動で消える |
| 共通UI | `resources/js/shared/Components/EmptyState.tsx` | 「該当なし」表示 |
| ユーティリティ | `resources/js/shared/lib/yen.ts` | `3桁区切りの金額表記` 形式の整形 |
| ユーティリティ | `resources/js/shared/lib/tone.ts` | ステータス配色の対の定数 |
| ユーティリティ | `resources/js/lib/utils.ts` | **修正**: `cn()`（clsx + tailwind-merge）を確認・整備 |

## インターフェース ＆ データロジック

### バリデーション

`RegisterRequest`:

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `name_kana` | 任意・文字列・最大100・全角カナのみ |
| `email` | 必須・メール形式・最大191・`users.email` で一意 |
| `password` | 必須・確認一致・8文字以上・`Password::defaults()` |
| `agree` | 必須・`accepted`（利用規約・プライバシーポリシーへの同意） |

`LoginRequest`:

| 項目 | ルール |
|---|---|
| `email` | 必須・メール形式 |
| `password` | 必須 |
| `remember` | 任意・boolean |

### 主要な処理フロー

**会員登録**: バリデーション → 会員ID採番（`M-` + `users` の最大連番 + 1、6桁ゼロ埋め）→ `users` に作成（`status = active`）→ ログイン → セッション再生成 → intended URL（なければ `/`）へリダイレクト

**ログイン**: レート制限の確認（メール＋IPで5回／分）→ 認証試行 → 失敗なら試行回数を加算し `email` にエラーを付けて戻す → 成功ならレート制限をクリア・セッション再生成 → intended URL（なければ `/`）へリダイレクト

**ログアウト**: ログアウト → セッション無効化 → CSRFトークン再生成 → `/` へリダイレクト

### 購入導線からの復帰

未ログインで `/checkout` 等の要認証ルートへアクセスした場合、Laravel 標準の `Authenticate` ミドルウェアが intended URL をセッションに保存する。ログイン成功後に `redirect()->intended()` で元の手続きへ戻す。カートページの「購入手続きへ進む」は未ログイン時にログイン画面を経由する。

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/Auth/RegisterTest.php` | 正常登録（会員IDの採番形式を含む）／メール重複／パスワード8文字未満／同意なし／登録後にログイン状態になること |
| `tests/Feature/Front/Auth/LoginTest.php` | 正常ログイン／誤パスワード／未登録メール／レート制限の発動／ログイン後のセッションID再生成／intended URL への復帰 |
| `tests/Feature/Front/Auth/LogoutTest.php` | ログアウトでセッションが無効化されること |
| `tests/Feature/Front/SharedPropsTest.php` | `HandleInertiaRequests` の共有データにパスワードハッシュ・メールアドレス等の機密情報が含まれないこと |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **日本語フォント**: 要件定義書は「Noto Sans JP」と記載しているが、モックのデザインシステム（`site.css`）は `Zen Kaku Gothic New` を使っている。`CLAUDE.local.md` が「UI はデザイン参照を正とする」と定めているため、推奨=**Zen Kaku Gothic New** を採用する。
2. **メール認証**: 実装しない（要件に記載なし）。`users.email_verified_at` は Laravel 標準として残すが使わない。
3. **パスワードリセット**: 実装しない（要件の画面一覧に含まれない）。`password_reset_tokens` テーブルは Laravel 標準として残すが使わない。
4. **ログイン失敗のレート制限**: 要件に記載はないが、認証機能のため推奨=**実装する**（メール＋IPで5回／分）。
5. **休会会員のログイン**: 推奨=`status = suspended` の会員はログインを拒否し、「アカウントが利用停止中です」を表示する。
6. **フォントの読み込み方法**: 推奨=Google Fonts の `@import`（モックと同じ）。オフライン要件がある場合はセルフホストへ変更する。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/front/auth.md`（会員登録・ログイン・ログアウト）
  - `docs/front/common-layout.md`（共通レイアウト・共通UIコンポーネント・デザイントークンの**正本**。以降の各フロント仕様書はここへリンクする）
- 本計画ファイルを削除し、トラッカーの状態を更新する
