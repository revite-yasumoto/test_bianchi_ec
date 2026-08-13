# 会員登録・ログイン・ログアウト

## 機能概要

- **対象画面・機能の目的:** `web` ガードによる会員の会員登録・ログイン・ログアウト。管理者（管理画面）とは完全に独立した認証（[docs/1_system_overview.md](../1_system_overview.md) 参照）。ゲスト購入は不可のため、購入導線は本機能の認証を前提とする。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/` | `top` | なし |
| GET | `/register` | `register` | `guest` |
| POST | `/register` | `register.store` | `guest` |
| GET | `/login` | `login` | `guest` |
| POST | `/login` | `login.store` | `guest` |
| POST | `/logout` | `logout` | `auth` |

- **アクセス権限・ミドルウェア:** 上表の通り。フロント側のルート名は `admin.` のような prefix を付けない。
- **本ドキュメントのスコープ:** 会員登録・ログイン・ログアウトのバックエンド処理、会員登録画面（`Register.tsx`）・ログイン画面（`Login.tsx`）。遷移先のTOPページは [docs/front/top.md](top.md) が正本。各画面が使う共通レイアウト・共通UIコンポーネント・デザイントークンは [docs/front/common-layout.md](common-layout.md) が正本。

## 使用テーブル

`users` テーブルを使用する。定義は [docs/2_database.md](../2_database.md) が正本。

`email_verified_at`（メール認証）・`password_reset_tokens` テーブル（パスワードリセット）は Laravel 標準として存在するが、本システムでは使用しない。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
ログイン（GET /login） 最大幅420px・中央寄せ:
+--------------------------------------+
|            ログイン                   |
|  ご購入には会員登録・ログインが必要です  |
|  メールアドレス                        |
|  [                                 ]  |
|  パスワード                            |
|  [                                 ]  |
|  [ ] この端末でログイン状態を保持する    |
|  [        ログイン        ]           |
|      新規会員登録はこちら →            |
+--------------------------------------+

会員登録（GET /register） 最大幅520px・中央寄せ:
+--------------------------------------+
|            会員登録                   |
|  お名前*          |  お名前（カナ）    |   ← SP幅では1カラム、sm以上で2カラム
|  メールアドレス*（全幅）                |
|  パスワード*（全幅）                    |
|  パスワード（確認）*（全幅）             |
|  [ ] 利用規約 および プライバシーポリシー |
|      に同意します                      |
|  [        登録する        ]           |
|  すでにアカウントをお持ちの方は ログイン  |
+--------------------------------------+
```

- エラー表示: 各入力欄の直下にインラインで表示する。認証失敗・レート制限・休会中のメッセージは `email` の直下にまとめて表示する。
- 必須項目のラベルには `*` を表示する。
- 同意チェックの「利用規約」「プライバシーポリシー」はそれぞれの静的ページ（[docs/front/static-pages.md](static-pages.md)）へのリンクにする。リンクはチェックボックスのラベル内に置く（ラベル内の対話的要素へのクリックではチェックが切り替わらない）。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

type RegisterForm = {
    name: string;
    name_kana: string;
    email: string;
    password: string;
    password_confirmation: string;
    agree: boolean;
};
```

共有プロパティ（`FrontSharedProps`）の型と共有範囲は [docs/front/common-layout.md](common-layout.md) が正本。

- **入力値バリデーションルール（`Front\Auth\RegisterRequest`）:**

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `name_kana` | 任意・文字列・最大100・全角カタカナ／長音符／空白のみ（`/\A[ァ-ヶー\x{3000}\s]+\z/u`） |
| `email` | 必須・文字列・メール形式・最大191・`users.email` で一意 |
| `password` | 必須・`password_confirmation` と一致・8文字以上 |
| `agree` | `accepted`（利用規約・プライバシーポリシーへの同意） |

- **入力値バリデーションルール（`Front\Auth\LoginRequest`）:**

| 項目 | ルール |
|---|---|
| `email` | 必須・文字列・メール形式 |
| `password` | 必須・文字列 |
| `remember` | 任意・boolean |

- レート制限: `email`（小文字化）＋ IPアドレスをキーに5回／分。超過時は残り秒数を含むメッセージを `email` に返す。

- **主要な処理フロー:**

**会員登録（`RegisteredUserController::store()`）:**
1. `RegisterRequest` でバリデーションする。
2. `GenerateMemberCode` で会員IDを採番する（`users.member_code` の最大値の数値部分 + 1、`M-` + 6桁ゼロ埋め。会員が1件も無い場合は `M-100001`）。
3. `users` に `status = active` で作成する。
4. `web` ガードでログインし、セッションを再生成する。
5. intended URL（無ければ `top`）へリダイレクトし、`success` フラッシュに「会員登録が完了しました。」を渡す。

**ログイン（`AuthenticatedSessionController::store()`）:**
1. レート制限を確認する（超過時は `email` にエラーを返す）。
2. `Auth::guard('web')->attempt()` で認証する。
3. 失敗時はレート制限のカウントを加算し、「メールアドレスまたはパスワードが誤っています。」を `email` に返す（アカウントの存在有無を区別しない）。
4. 認証に成功しても `status` が `suspended` の会員はログアウトさせ、「アカウントが利用停止中です。お問い合わせください。」を `email` に返す。
5. 成功時はレート制限をクリアし、セッションを再生成（`session()->regenerate()`）した上で intended URL（無ければ `top`）へリダイレクトする。

**ログアウト（`AuthenticatedSessionController::destroy()`）:** `web` ガードからログアウト → セッション無効化（`invalidate()`） → CSRFトークン再生成（`regenerateToken()`） → `top` へリダイレクトし、`success` フラッシュに「ログアウトしました。」を渡す。

## 業務ルール

- ログイン失敗時のメッセージは、メールアドレスの登録有無を区別できる内容にしない（アカウント列挙攻撃対策）。休会中のメッセージのみ、利用者が問い合わせ先を判断できるよう区別して返す。
- メール認証・パスワードリセットは実装しない（要件の画面一覧に含まれないため）。
- 会員IDの採番は登録時点の最大値 + 1 で行うため、同時登録が競合した場合は `users.member_code` の一意制約で弾かれる。
- 未ログインで要認証ルートへアクセスした場合、Laravel 標準の `Authenticate` ミドルウェアが intended URL をセッションに保存し、ログイン成功後に元の手続きへ戻す。フロント向けの遷移先は `bootstrap/app.php` の `redirectGuestsTo`（`login`）／`redirectUsersTo`（`top`）で決まる。管理画面向けの分岐は [docs/admin/auth.md](../admin/auth.md) が正本。

## 関連ドキュメント

- [docs/front/common-layout.md](common-layout.md) — フロント共通レイアウト・共通UIコンポーネント・デザイントークンの正本。`FrontSharedProps` の型定義もこちらが正本
- [docs/front/top.md](top.md) — ログイン後・ログアウト後の遷移先であるTOPページの正本
- [docs/front/static-pages.md](static-pages.md) — 同意チェックからリンクする利用規約・プライバシーポリシーの正本
- [docs/admin/auth.md](../admin/auth.md) — 管理者認証の正本。`bootstrap/app.php` のパス分岐・`config/inertia.php` の設定はこちらが正本
- [docs/1_system_overview.md](../1_system_overview.md) — 2ガード構成の前提
- [docs/2_database.md](../2_database.md) — `users` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Front/Auth/AuthenticatedSessionController.php` |
| Controller | `app/Http/Controllers/Front/Auth/RegisteredUserController.php` |
| FormRequest | `app/Http/Requests/Front/Auth/LoginRequest.php` |
| FormRequest | `app/Http/Requests/Front/Auth/RegisterRequest.php` |
| Action | `app/Actions/Front/Auth/GenerateMemberCode.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/front/Pages/Auth/Login.tsx` |
| Page | `resources/js/front/Pages/Auth/Register.tsx` |
| Test | `tests/Feature/Front/Auth/LoginTest.php` |
| Test | `tests/Feature/Front/Auth/LogoutTest.php` |
| Test | `tests/Feature/Front/Auth/RegisterTest.php` |

## 受け入れ条件

- 会員登録画面が正しいInertiaコンポーネントで表示される: `tests/Feature/Front/Auth/RegisterTest.php`（`会員登録画面が表示される`）
- 会員登録するとユーザーが作成されログイン状態になる: 同上（`会員登録するとユーザーが作成されログイン状態になる`）
- 会員が1件も無いとき会員IDが `M-100001` で採番される: 同上（`会員が1件も無いとき会員番号は開始番号で採番される`）
- 会員IDが既存の最大連番 + 1 で採番される: 同上（`会員番号は既存の最大連番の次の番号で採番される`）
- 登録済みメールアドレスでは登録できない: 同上（`登録済みのメールアドレスでは登録できない`）
- パスワードが8文字未満・確認用と不一致では登録できない: 同上（`パスワードが8文字未満では登録できない`・`パスワードと確認用パスワードが一致しないと登録できない`）
- 同意チェックなしでは登録できない: 同上（`利用規約に同意しないと登録できない`）
- 氏名カナは全角カタカナのみ受け付け、未入力は許可される: 同上（`氏名カナが全角カタカナ以外では登録できない`・`氏名カナは未入力でも登録できる`）
- ログイン済みは会員登録画面を開けない: 同上（`ログイン済みの会員は会員登録画面を開けない`）
- ログイン画面が正しいInertiaコンポーネントで表示される: `tests/Feature/Front/Auth/LoginTest.php`（`ログイン画面が表示される`）
- 正しい認証情報でログインできる: 同上（`正しいメールアドレスとパスワードでログインできる`）
- 誤パスワード・未登録メールではログインできない: 同上（`パスワードが誤っている場合はログインできない`・`未登録のメールアドレスではログインできない`）
- 休会中の会員はログインできない: 同上（`休会中の会員はログインできない`）
- 5回失敗するとレート制限される: 同上（`ログイン失敗が5回続くとレート制限がかかる`）
- ログイン成功時にセッションIDが再生成される: 同上（`ログイン成功時にセッション識別子が再生成される`）
- ログイン後に認証前のURLへ復帰する: 同上（`ログイン後は認証前にアクセスしようとしたページへ戻る`）
- 未ログインで要認証ルートへアクセスするとログイン画面へリダイレクトされる: 同上（`未ログインで要認証ルートへアクセスするとログイン画面へ送られる`）
- ログイン済みはログイン画面を開けない: 同上（`ログイン済みの会員はログイン画面を開けない`）
- ログアウトでTOPへ戻り未認証になる: `tests/Feature/Front/Auth/LogoutTest.php`（`ログアウトするとトップページへ戻り未認証になる`）
- ログアウトでセッションが無効化される: 同上（`ログアウト時にセッションが無効化される`）
- 会員登録・ログイン画面のレスポンシブ表示（SP1カラム／sm以上2カラム）: 自動テストなし。目視確認で担保する
