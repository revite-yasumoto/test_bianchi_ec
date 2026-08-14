# フロント パスワードリセット

## 機能概要

- **対象画面・機能の目的:** パスワードを忘れた会員が、登録済みのメールアドレスへ送られる再設定URLから新しいパスワードを設定する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/forgot-password` | `password.request` | `guest` |
| POST | `/forgot-password` | `password.email` | `guest` |
| GET | `/reset-password/{token}` | `password.reset` | `guest` |
| POST | `/reset-password` | `password.update` | `guest` |

- **アクセス権限・ミドルウェア:** すべて `guest`。ログイン中の会員は開けない（パスワードの変更は [docs/front/mypage-profile.md](mypage-profile.md) の会員情報変更が担う）。
- **本ドキュメントのスコープ:** 再設定の申請・再設定の2画面と、トークンの発行・検証・パスワードの更新。メール本文は [docs/mail-notification.md](../mail-notification.md) が正本。

Laravel 標準のパスワードブローカー（`Illuminate\Support\Facades\Password`）を使う。トークンの発行・保存・検証・失効は同機能が担い、独自実装しない。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `password_reset_tokens` | 再設定トークン。`email` が主キーで、1アドレスにつき常に最新の1件のみ保持する |
| `users` | `password` の更新と `remember_token` の再生成 |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
パスワード再設定の申請（GET /forgot-password） 最大幅420px・中央寄せ:
+--------------------------------------+
|         パスワードの再設定             |
|  ご登録のメールアドレスを入力してくだ   |
|  さい。再設定用のリンクをお送りします。  |
|  メールアドレス*                       |
|  [                                 ]  |
|  [      再設定メールを送る      ]      |
|      ログイン画面へ戻る →              |
+--------------------------------------+

パスワードの再設定（GET /reset-password/{token}） 最大幅420px・中央寄せ:
+--------------------------------------+
|         パスワードの再設定             |
|  メールアドレス*（メールのリンクから    |
|  自動入力・変更可）                    |
|  [                                 ]  |
|  新しいパスワード*                     |
|  [                                 ]  |
|  新しいパスワード（確認）*              |
|  [                                 ]  |
|  [    パスワードを変更する    ]        |
+--------------------------------------+
```

- エラー表示: 各入力欄の直下にインラインで表示する。トークンの期限切れ・不一致は `email` の直下に表示する。
- 申請の送信後は同じ画面に留まり、成功メッセージをトーストで出す。

## インターフェース ＆ データロジック

### データ構造・型定義

申請画面（`ForgotPassword.tsx`）は props を受け取らない。完了・エラーの通知は共通レイアウトのトーストとインラインのエラー表示が担う。

```ts
// resources/js/front/Pages/Auth/ResetPassword.tsx
type Props = {
    token: string;   // URL の {token}
    email: string;   // クエリ ?email= の値
};
```

### 入力値バリデーションルール

**申請（`Front\Auth\PasswordResetLinkRequest`）**

| 項目 | ルール |
|---|---|
| `email` | 必須・文字列・メール形式 |

**再設定（`Front\Auth\NewPasswordRequest`）**

| 項目 | ルール |
|---|---|
| `token` | 必須・文字列 |
| `email` | 必須・文字列・メール形式 |
| `password` | 必須・確認一致・8文字以上 |

パスワードの下限は会員登録・会員情報変更と同じ8文字にそろえる。

### 主要な処理フロー

**再設定の申請（`PasswordResetLinkController::store()`）**

1. 入力を検証する。
2. `Password::sendResetLink()` を呼ぶ。該当する会員がいればトークンを発行し、再設定メールを送る。
3. **結果にかかわらず同じ成功メッセージを返す。** 送信できなかった場合（未登録・再送制限中）もメッセージを変えない。メッセージは `success` フラッシュで渡し、共通レイアウトのトーストで表示する。

**再設定（`NewPasswordController::store()`）**

1. 入力を検証する。
2. `Password::reset()` でトークンを検証し、一致すればパスワードを更新して `remember_token` を再生成する。
3. 成功したらログイン画面へ遷移し、`success` フラッシュで完了メッセージを渡す。**自動ログインはしない**（会員登録と同じ扱い。[docs/front/auth.md](auth.md) が正本）。
4. トークンが無効・期限切れの場合は `email` にエラーを返す。

## 業務ルール

- トークンの有効期限は60分、同一アドレスへの再送は60秒の間隔を空ける（`config/auth.php` の `passwords.users`）。
- 申請の結果を成否で出し分けない。出し分けると未登録のアドレスを判別できてしまう（[docs/front/auth.md](auth.md) のログイン失敗メッセージと同じ方針）。
- 休会中（`suspended`）の会員もパスワードを再設定できる。再設定後のログインで休会の案内が出るため、ここで拒否しても伝わる情報は変わらない。
- パスワードの再設定に成功しても自動ログインはしない。
- メール認証（`email_verified_at`）は引き続き使用しない。

## 関連ドキュメント

- [docs/front/auth.md](auth.md) — ログイン・会員登録の正本。パスワード再設定への導線を持つ
- [docs/mail-notification.md](../mail-notification.md) — 再設定メールの正本
- [docs/front/mypage-profile.md](mypage-profile.md) — ログイン中のパスワード変更（本機能とは別経路）
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout` の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/Auth/PasswordResetLinkController.php` |
| Controller | `app/Http/Controllers/Front/Auth/NewPasswordController.php` |
| FormRequest | `app/Http/Requests/Front/Auth/PasswordResetLinkRequest.php` |
| FormRequest | `app/Http/Requests/Front/Auth/NewPasswordRequest.php` |
| Model | `app/Models/User.php` |
| Mailable | `app/Mail/Front/PasswordResetLink.php` |
| View | `resources/views/emails/front/password-reset-link.blade.php` |
| Page | `resources/js/front/Pages/Auth/ForgotPassword.tsx` |
| Page | `resources/js/front/Pages/Auth/ResetPassword.tsx` |
| Test | `tests/Feature/Front/Auth/PasswordResetTest.php` |

## 受け入れ条件

- 申請画面・再設定画面を未ログインで開ける: `tests/Feature/Front/Auth/PasswordResetTest.php`（`未ログインでパスワード再設定の申請画面を開ける`・`トークン付きのリンクから再設定画面を開ける`）
- 登録済みのアドレスに再設定メールが送られる: 同上（`登録済みのアドレスに再設定メールが送られる`）
- 未登録のアドレスでも同じ成功メッセージを返す: 同上（`未登録のアドレスでもメールは送られず同じ応答になる`）
- 有効なトークンでパスワードを変更でき、新しいパスワードでログインできる: 同上（`再設定したパスワードでログインできる`）
- 再設定に成功してもログイン状態にはならない: 同上（`パスワードを再設定してもログイン状態にはならない`）
- 無効なトークン・期限切れのトークンでは変更できない: 同上（`無効なトークンではパスワードを変更できない`・`期限切れのトークンではパスワードを変更できない`）
- 8文字未満・確認用と不一致では変更できない: 同上（`八文字未満のパスワードには変更できない`・`確認用パスワードが一致しなければ変更されない`）
- ログイン中は申請画面を開けない: 同上（`ログイン中は申請画面を開けない`）
- 画面のレイアウト・トーストの表示: 自動テストなし。目視確認で担保する
