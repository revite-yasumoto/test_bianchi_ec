# フロント 退会

## 機能概要

- **対象画面・機能の目的:** 会員が自分の意思でサービスの利用を終える。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/mypage/withdrawal` | `mypage.withdrawal` | `auth` |
| POST | `/mypage/withdrawal` | `mypage.withdrawal.store` | `auth` |

- **アクセス権限・ミドルウェア:** 要ログイン。操作できるのは自分自身のアカウントのみで、他の会員を指定する経路を持たない。
- **本ドキュメントのスコープ:** 退会の確認画面と実行。管理画面での退会会員の見え方は [docs/admin/member.md](../admin/member.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `users` | `status` を `withdrawn` へ更新する。行そのものは削除しない |

`orders.user_id` は `restrictOnDelete` のため、注文のある会員は物理削除できない。退会はステータスの変更で表す。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
退会（GET /mypage/withdrawal） マイページのタブ配下・最大幅560px:
+------------------------------------------+
|  退会手続き                                |
|  退会すると以下のとおりになります。          |
|   ・ログインできなくなります                 |
|   ・同じメールアドレスでの再登録はできません   |
|   ・ご注文の履歴は保持されます               |
|   ・退会後の取り消しはできません             |
|                                          |
|  ご本人確認のため、パスワードを入力してください  |
|  パスワード*                               |
|  [                                     ]  |
|  [ ] 上記の内容を確認しました                |
|  [        退会する        ]  [ 戻る ]      |
+------------------------------------------+
```

- 退会ボタンは同意チェックが入るまで押せない。
- マイページのタブには並べず、[docs/front/mypage-profile.md](mypage-profile.md) の会員情報変更画面の下部から遷移する。日常的に触る画面ではないため。

## インターフェース ＆ データロジック

### データ構造・型定義

props は受け取らない。表示する文言はすべて画面に固定で持つ。

### 入力値バリデーションルール（`Front\MyPage\WithdrawRequest`）

| 項目 | ルール |
|---|---|
| `password` | 必須・`current_password:web`（ログイン中の会員のパスワードと一致すること） |
| `agree` | 必須・`accepted` |

### 主要な処理フロー

**退会の実行（`WithdrawalController::store()`）**

1. 入力を検証する。パスワードが違えば `password` にエラーを返す。
2. `WithdrawUser` が `users.status` を `withdrawn` に更新し、続けて退会完了メールを会員へ送る。送信の失敗は退会の成立を妨げない。
3. ログアウトし、セッションを無効化してCSRFトークンを再生成する。ログアウトは記憶トークンも作り直すため、他端末の「ログイン状態を保持」による再ログインもここで断たれる。
4. TOPへリダイレクトし、`success` フラッシュに退会完了の文言を渡す。

## 業務ルール

- 退会は取り消せない。復帰を希望する会員には新しいメールアドレスでの再登録を案内する。
- 退会後も `users` の行は残るため、同じメールアドレスでの再登録はできない（`users.email` の一意制約による）。
- 注文・配送先・お気に入り・カートのデータは削除しない。ログインできなくなるため会員自身からは参照されず、管理画面からは従来どおり注文を追える。
- 退会した会員のステータスを管理画面から戻すことはできない（[docs/admin/member.md](../admin/member.md) が正本）。
- 退会の完了を会員へメールで知らせる。送信の仕様は [docs/mail-notification.md](../mail-notification.md) が正本。管理者への通知は送らない（退会は管理画面の会員一覧から把握できるため）。
- 退会で断てるのは、操作した端末のセッションと「ログイン状態を保持」による再ログインまで。**別の端末でログイン中のセッションはそのまま残り、その有効期限が切れるまで操作できる。** ステータスを毎リクエスト検証する仕組みは持たないため（同じことは休会にした会員にも当てはまる）。

## 関連ドキュメント

- [docs/front/mypage-profile.md](mypage-profile.md) — 退会画面への導線を持つ会員情報変更
- [docs/front/auth.md](auth.md) — ログイン時のステータス判定の正本
- [docs/mail-notification.md](../mail-notification.md) — 退会完了メールの正本
- [docs/admin/member.md](../admin/member.md) — 管理画面での退会会員の表示・操作制限の正本
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout`・マイページのタブの正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/MyPage/WithdrawalController.php` |
| Action | `app/Actions/Front/MyPage/WithdrawUser.php` |
| FormRequest | `app/Http/Requests/Front/MyPage/WithdrawRequest.php` |
| Enum | `app/Enums/UserStatus.php` |
| Mailable | `app/Mail/Front/WithdrawalCompleted.php` |
| View | `resources/views/emails/front/withdrawal-completed.blade.php` |
| Page | `resources/js/front/Pages/MyPage/Withdrawal.tsx` |
| 型定義 | `resources/js/shared/lib/enums.ts` |
| Test | `tests/Feature/Front/MyPage/WithdrawalTest.php` |

## 受け入れ条件

- 未ログインでは退会画面を開けない: `tests/Feature/Front/MyPage/WithdrawalTest.php`（`未ログインではログイン画面へリダイレクトされる`）
- ログイン中は退会画面を開ける: 同上（`退会画面が表示される`）
- パスワードと同意チェックが揃えば退会できる: 同上（`退会するとステータスが退会になりログアウトする`）
- パスワードが違えば退会されない: 同上（`パスワードが誤っていれば退会されない`）
- 同意チェックが無ければ退会されない: 同上（`同意していなければ退会されない`）
- 退会した会員はログインできない: 同上（`退会した会員はログインできない`）
- 退会しても注文が残る: 同上（`退会しても注文は残る`）
- 退会で記憶トークンが作り直される（ログアウトによる）: 同上（`退会すると記憶トークンが作り直される`）
- 退会の完了で会員へメールが送られる: 同上（`退会すると完了メールが送られる`・`退会に失敗したときはメールが送られない`）
- 別端末の既存セッションが退会後も生きること: 自動テストなし。仕様として受け入れている挙動で、塞ぐにはステータスを毎リクエスト検証するミドルウェアの追加が要る
- 退会後に同じメールアドレスで登録できない: 同上（`退会したメールアドレスでは再登録できない`）
- 画面のレイアウト・同意チェックによるボタンの活性: 自動テストなし。目視確認で担保する
