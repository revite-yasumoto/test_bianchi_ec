# 管理者ログイン・ログアウト

## 機能概要

- **対象画面・機能の目的:** `admin` ガードによる管理者のログイン・ログアウト。会員（フロント）とは完全に独立した認証（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/login` | `admin.login` | `guest:admin` |
| POST | `/admin/login` | `admin.login.store` | `guest:admin` |
| POST | `/admin/logout` | `admin.logout` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。以降の単位が追加する管理画面ルートは全て `auth:admin` グループ内に置く。
- **本ドキュメントのスコープ:** 管理者ログイン・ログアウトのバックエンド処理とログイン画面（`Login.tsx`）。ログイン後の遷移先であるダッシュボードは [docs/admin/dashboard.md](dashboard.md) が正本。ログイン後に使う管理画面共通レイアウト（サイドバー・ヘッダー・共通UIコンポーネント）は [docs/admin/common-layout.md](common-layout.md) が正本。

## 使用テーブル

`admins` テーブルを使用する。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
PC幅（md以上）:
+----------------------+--------------------------------+
| ブランドパネル（42%） | ログインフォーム（残り幅・中央寄せ） |
| Bianchi              |  管理者ログイン                  |
| ADMIN CONSOLE        |  会員アカウントではログインできません |
|                       |  [管理者ID / メールアドレス]     |
| 受注から在庫まで、     |  [パスワード]                   |
| ひとつの画面で。       |  [ ] この端末でログイン状態を保持する |
|                       |  [ログイン]                     |
| SECURE AREA/STAFF ONLY|  パスワードをお忘れの場合は...   |
+----------------------+--------------------------------+

SP幅（md未満）: ブランドパネルとフォームを縦積みにする（flex-col）。
```

- エラー表示: `login_id`・`password` 各項目の直下にインラインで表示する。認証失敗・レート制限のメッセージは `login_id` の直下にまとめて表示する。
- デモ用アカウント（`admin@bianchi.demo` 等）の情報は画面に表示しない。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type LoginForm = {
    login_id: string;
    password: string;
    remember: boolean;
};

type AdminAuthUser = {
    name: string;
    email: string;
};

type CsvImportResult = {
    created: number;
    updated: number;
    errors: { line: number; message: string }[];
};

type AdminSharedProps = {
    auth: { admin: AdminAuthUser | null };
    flash: { importResult: CsvImportResult | null };
};
```

`AdminSharedProps` は `/admin/*` へのInertiaリクエストにのみ共有される（`front` 側のページには `auth.admin` キー自体が存在しない）。共有するカラムは `Sidebar` が実際に表示する `name`・`email` のみに絞る（allowlist）。`id`・`admin_code` 等、参照側で使っていないカラムは追加しない。

`flash.importResult` はCSVインポートの結果を画面へ渡すためのもの。中身の意味は [docs/admin/csv.md](csv.md) が正本。

- **入力値バリデーションルール（`Admin\Auth\LoginRequest`）:**

| 項目 | ルール |
|---|---|
| `login_id` | 必須・文字列・最大191（管理者ID または メールアドレス） |
| `password` | 必須・文字列・8文字以上 |
| `remember` | 任意・boolean |

- レート制限: `login_id`（小文字化）＋ IPアドレスをキーに5回／分。超過時は残り秒数を含むメッセージを `login_id` に返す。

- **主要な処理フロー:**

**ログイン（`store()`）:**
1. レート制限を確認する（超過時は `login_id` にエラーを返す）。
2. `login_id` が `@` を含むかで `email` / `admin_code` のどちらで検索するかを判定する。
3. `Auth::guard('admin')->attempt()` で認証する。
4. 失敗時はレート制限のカウントを加算し、「管理者アカウントが見つからないか、パスワードが誤っています」を `login_id` にまとめて返す（アカウントの存在有無を区別しない）。
5. 成功時はレート制限をクリアし、セッションを再生成（`session()->regenerate()`）した上で `AuthenticatedSessionController::landingUrl()`（`admin.dashboard`）へリダイレクトする。

**ログアウト（`destroy()`）:** `admin` ガードからログアウト → セッション無効化（`invalidate()`） → CSRFトークン再生成（`regenerateToken()`） → `admin.login` へリダイレクト。フロント側は `Sidebar` の確認モーダル（[docs/admin/common-layout.md](common-layout.md) 参照）を経てこのエンドポイントを呼ぶ。

## 業務ルール

- ログイン失敗時のメッセージは、管理者IDの存在有無を区別できる内容にしない（アカウント列挙攻撃対策）。
- ログイン成功時とログイン画面へのゲスト制限時の遷移先は `AuthenticatedSessionController::landingUrl()` に集約し、ダッシュボード（`admin.dashboard`）を返す。
- `bootstrap/app.php` の `redirectGuestsTo` / `redirectUsersTo` はリクエストパスが `admin/*` かどうかで管理者向け・会員向けの遷移先を振り分ける。会員向け分岐の遷移先は [docs/front/auth.md](../front/auth.md) が正本。`admin/*` は `admin`（`admin.dashboard` のような末尾セグメントなしのパス）にマッチしないため、`$request->is('admin/*') || $request->is('admin')` の形で両方を判定する（`HandleInertiaRequests::share()` も同様）。
- `config/inertia.php` の `testing.ensure_pages_exist` は `false` にする。`front/Xxx`・`admin/Xxx` の形式のページ名を `resources/js/{area}/Pages/...` へ解決するカスタムresolve（`resources/js/app.tsx`）を使っており、`path + component名` を単純連結するInertiaパッケージ標準のファイル存在チェックとは前提が合わないため。

## 関連ドキュメント

- [docs/admin/dashboard.md](dashboard.md) — ログイン後の遷移先（`GET /admin`）の正本
- [docs/admin/common-layout.md](common-layout.md) — 管理画面共通レイアウト・共通UIコンポーネントの正本。ログイン後の画面はここのAdminLayoutを使う
- [docs/front/auth.md](../front/auth.md) — 会員認証の正本。`HandleInertiaRequests::share()` の会員向け分岐・`bootstrap/app.php` の会員向け遷移先はこちらに対応する
- [docs/1_system_overview.md](../1_system_overview.md) — 2ガード構成の前提
- [docs/2_database.md](../2_database.md) — `admins` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/Auth/AuthenticatedSessionController.php` |
| FormRequest | `app/Http/Requests/Admin/Auth/LoginRequest.php` |
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` |
| 設定 | `bootstrap/app.php` |
| 設定 | `config/inertia.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Auth/Login.tsx` |
| 型 | `resources/js/types/global.d.ts` |
| Test | `tests/Feature/Admin/Auth/LoginTest.php` |
| Test | `tests/Feature/Admin/Auth/LogoutTest.php` |
| Test | `tests/Feature/Admin/Auth/GuardSeparationTest.php` |
| Test | `tests/Feature/Admin/AdminSharedPropsTest.php` |
| Test | `tests/Feature/Admin/HomeTest.php` |

## 受け入れ条件

- ログイン画面が正しいInertiaコンポーネントで表示される: `tests/Feature/Admin/Auth/LoginTest.php`（`login_page_renders`）
- 管理者IDでログインできる: 同上（`admin_can_login_with_admin_code`）
- メールアドレスでログインできる: 同上（`admin_can_login_with_email`）
- 誤パスワードでログインできない: 同上（`login_fails_with_wrong_password`）
- 未登録アカウントでログインできない: 同上（`login_fails_for_unregistered_account`）
- `login_id` 未入力はバリデーションエラーになる: 同上（`login_requires_login_id`）
- `password` が8文字未満はバリデーションエラーになる: 同上（`login_requires_password_of_at_least_eight_characters`）
- 5回失敗するとレート制限される: 同上（`login_is_rate_limited_after_five_failed_attempts`）
- ログイン成功時にセッションIDが再生成される: 同上（`successful_login_regenerates_session_id`）
- ログイン成功後にダッシュボードへ遷移し、正しいInertiaコンポーネントで表示される: 同上（`successful_login_redirects_to_a_working_page`）
- ログアウトできる: `tests/Feature/Admin/Auth/LogoutTest.php`（`admin_can_logout`）
- ログアウトでセッションが無効化される（データ破棄＋ID再生成）: 同上（`logout_invalidates_the_session`）
- 未ログインはログアウトエンドポイントにアクセスできない: 同上（`guest_cannot_access_logout_route`）
- 会員（`web`ガード）は管理者専用ルートにアクセスできない: `tests/Feature/Admin/Auth/GuardSeparationTest.php`（`member_session_cannot_access_admin_only_route`）
- 管理者（`admin`ガード）は会員専用ルートにアクセスできない: 同上（`admin_session_cannot_access_member_only_route`）
- 共有データが `name`・`email` のみで、パスワードハッシュ・`remember_token`・`id`・`admin_code` を含まない: `tests/Feature/Admin/AdminSharedPropsTest.php`（`shared_props_expose_admin_name_and_email_only`）
- `auth.admin` は管理画面（`/admin/*`）以外には共有されない: 同上（`shared_props_do_not_expose_admin_key_outside_admin_paths`）
- ログイン画面のレスポンシブ表示（PC 2カラム／SP縦積み）: 自動テストなし。目視確認で担保する
