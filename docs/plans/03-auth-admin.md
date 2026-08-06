# 単位03: 管理者認証＋管理画面共通レイアウト

依存: 単位01

管理画面側の土台。会員（フロント）とは独立した管理者認証と、以降の全管理画面が使う共通レイアウト・共通UIを整備する。

## スコープ

- 管理者のログイン・ログアウト（`admin` ガード。会員アカウントではログインできない）
- 管理画面共通レイアウト（ドロップダウン式サイドメニュー・ヘッダー・トースト）
- 以降の管理画面で共通利用するUIコンポーネント（コンパクト一覧テーブル・絞り込みバー・確認モーダル）
- サイドメニューは全10メニュー分の項目を定義するが、未実装の単位のリンクは本単位では遷移先がないため、実装済みの単位から順に有効化する

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Middleware） | `laravel-set:laravel` |
| `.tsx` / `.ts`（Page / Layout / Component） | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php`）

`Route::prefix('admin')->name('admin.')` のグループ内に定義する。

| メソッド | パス | ルート名 | 認証 | 内容 |
|---|---|---|---|---|
| GET | `/admin/login` | `admin.login` | guest:admin | 管理者ログインフォーム |
| POST | `/admin/login` | `admin.login.store` | guest:admin | ログイン実行 |
| POST | `/admin/logout` | `admin.logout` | auth:admin | ログアウト |

以降の単位で追加する管理画面ルートは、すべて `auth:admin` ミドルウェアを適用したグループ内に置く。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/Auth/AuthenticatedSessionController.php` | `create()` / `store()` / `destroy()` |
| FormRequest | `app/Http/Requests/Admin/Auth/LoginRequest.php` | 管理者ID / メールアドレスの両対応・レート制限 |
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` | **修正**: 管理画面向けに `auth.admin`（id / admin_code / name / email）を共有。会員向けの共有データと排他にする |
| 設定 | `bootstrap/app.php` | **修正**: `admin` ガード用の認証リダイレクト先を `admin.login` に設定 |
| 設定 | `config/auth.php` | **確認**: 単位01で追加した `admin` ガード・`admins` プロバイダが機能すること |
| ルート | `routes/web.php` | **修正**: 上表のルートと `auth:admin` グループの枠を追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Layout | `resources/js/admin/Layouts/AdminLayout.tsx` | サイドバー＋ヘッダー＋コンテンツ枠＋トースト |
| Component | `resources/js/admin/Components/Sidebar.tsx` | ロゴ・ドロップダウンメニュー・フッターの管理者情報＋ログアウト |
| Component | `resources/js/admin/Components/SidebarMenu.ts` | メニュー定義（下表）。子項目を持つグループは開閉可能 |
| Component | `resources/js/admin/Components/PageHeader.tsx` | ページタイトル＋右寄せのアクションボタン群 |
| Component | `resources/js/admin/Components/FilterBar.tsx` | 白カード内に絞り込み入力を横並びに配置。右端に件数表示、クリアボタンを共通設置 |
| Component | `resources/js/admin/Components/DataTable.tsx` | コンパクト表示の一覧テーブル。横スクロール対応。0件時は `EmptyState` を表示 |
| Component | `resources/js/admin/Components/ConfirmDialog.tsx` | 確認モーダル（タイトル・本文・実行ボタンのラベルと配色を受け取る） |
| Page | `resources/js/admin/Pages/Auth/Login.tsx` | 左に紺のブランドパネル、右にログインフォームの2カラム。SPでは縦積み |
| 型 | `resources/js/types/global.d.ts` | **修正**: `AdminSharedProps` を追加 |

### サイドメニュー定義

| 表示名 | 子項目 | 実装単位 |
|---|---|---|
| ダッシュボード | - | 12 |
| 注文管理 | - | 07 |
| 商品管理（グループ） | 商品一覧 / 商品登録 / 規格管理 / カテゴリ管理 / 商品CSV登録 / 在庫 | 05 / 05 / 04 / 04 / 09 / 06 |
| 会員マスタ | - | 08 |
| 管理者マスタ | - | 08 |
| 送料設定マスタ | - | 10 |
| EC基本設定 | - | 10 |
| 新着ニュース管理 | - | 11 |
| 重要なお知らせ管理 | - | 11 |

グループの開閉状態は、現在のページが属するグループを既定で開いた状態にする。

## インターフェース ＆ データロジック

### バリデーション

`Admin\Auth\LoginRequest`:

| 項目 | ルール |
|---|---|
| `login_id` | 必須・文字列・最大191（管理者ID または メールアドレス） |
| `password` | 必須・8文字以上 |
| `remember` | 任意・boolean |

### 主要な処理フロー

**ログイン**:
1. レート制限の確認（`login_id` ＋ IP で5回／分）
2. `login_id` が `@` を含むかで `email` / `admin_code` を判定し、`admins` から該当行を取得
3. `Auth::guard('admin')->attempt()` で認証
4. 失敗時は試行回数を加算し、「管理者アカウントが見つからないか、パスワードが誤っています」を `login_id` にまとめて返す（管理者IDの存在有無を区別できるメッセージにしない）
5. 成功時はレート制限をクリア・セッション再生成 → `admin.dashboard`（単位12の実装前は `admin.orders.index`）へリダイレクト

**ログアウト**: 確認モーダルで確認 → `admin` ガードからログアウト → セッション無効化 → CSRFトークン再生成 → `admin.login` へリダイレクト

### 会員アカウントとの分離

- `web` ガードと `admin` ガードはセッションキーが独立するため、会員としてログイン中でも管理画面には入れない
- `users` テーブルと `admins` テーブルは完全に別。同一メールアドレスが両方に存在してもよい

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Auth/LoginTest.php` | 管理者IDでのログイン／メールアドレスでのログイン／誤パスワード／未登録／レート制限／セッションID再生成 |
| `tests/Feature/Admin/Auth/LogoutTest.php` | ログアウトでセッションが無効化されること |
| `tests/Feature/Admin/Auth/GuardSeparationTest.php` | 会員（`web` ガード）でログイン中に `auth:admin` ルートへアクセスすると `admin.login` にリダイレクトされること。逆方向も同様 |
| `tests/Feature/Admin/AdminSharedPropsTest.php` | 共有データにパスワードハッシュが含まれないこと |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **デモ用アカウントの画面表示**: モックのログイン画面には「デモ用アカウント admin@veloce.demo / admin1234」の案内枠がある。推奨=**表示しない**（認証情報を画面に出さない）。デモ用アカウントは Seeder で作成し、パスワードは環境変数で管理する。
2. **ログイン状態の保持**: モックに「この端末でログイン状態を保持する」チェックがある。推奨=`remember` として実装する。
3. **管理者ログイン失敗のレート制限**: 推奨=**実装する**（5回／分）。
4. **管理者の2要素認証・IP制限**: 実装しない（要件に記載なし）。
5. **ログイン後の遷移先**: 単位12（ダッシュボード）の実装前は `admin.orders.index`、実装後は `admin.dashboard` へ変更する。
6. **サイドメニューの未実装リンク**: 推奨=各単位の実装時にその単位のリンクを有効化する（未実装リンクを押せる状態で残さない）。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/auth.md`（管理者ログイン・ログアウト）
  - `docs/admin/common-layout.md`（管理画面共通レイアウト・共通UIコンポーネントの**正本**。以降の各管理画面仕様書はここへリンクする）
- 本計画ファイルを削除し、トラッカーの状態を更新する
