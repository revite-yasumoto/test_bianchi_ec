# メール通知

## 機能概要

- **対象画面・機能の目的:** 会員登録・お問い合わせ・注文の各操作を、会員および管理者へメールで通知する。
- **URL / メソッド:** なし（画面を持たない。各操作の処理中に送信される）。
- **アクセス権限・ミドルウェア:** なし（送信の契機となる各画面の権限に従う）。
- **本ドキュメントのスコープ:** 送信基盤（送信元・宛先・失敗時の扱い・レイアウト）と、会員登録・お問い合わせ・注文・パスワード再設定・出荷完了・退会完了の8通。

**本ドキュメントはメール送信の共通仕様の正本である。** 送信元・管理者の宛先・送信失敗時の扱い・レイアウトの記述はここに置き、各画面の仕様書からは本ドキュメントへリンクする。

## 使用テーブル

メール送信自体はテーブルを持たない。本文に使う値の取得元は次のとおり。

| テーブル | 用途 |
|---|---|
| `users` | 会員登録完了メールの氏名・会員ID・メールアドレス |
| `contacts` | お問い合わせの通知・控えの本文 |
| `orders` | 注文受付・注文通知の注文番号・金額・配送先・振込案内文 |
| `order_items` | 注文受付メールの明細 |

定義は [docs/2_database.md](2_database.md) が正本。

## 送信するメール

| # | メール | 宛先 | 送信の契機 |
|---|---|---|---|
| 1 | 会員登録完了 | 登録した会員 | 会員登録の完了時 |
| 2 | お問い合わせ受付通知 | 管理者 | お問い合わせの送信時 |
| 3 | お問い合わせ控え | フォームに入力されたアドレス | 同上 |
| 4 | 注文受付 | 注文した会員 | 注文の確定時 |
| 5 | 注文通知 | 管理者 | 同上 |
| 6 | パスワード再設定 | 申請したアドレスの会員 | パスワード再設定の申請時 |
| 7 | 出荷完了 | 注文した会員 | 管理者が出荷済みへ変更し、送信を選んだとき |
| 8 | 退会完了 | 退会した会員 | 退会手続きの完了時 |

## インターフェース ＆ データロジック

### 設定

| キー | 参照 | 内容 |
|---|---|---|
| `MAIL_FROM_ADDRESS` | `config('mail.from.address')` | 送信元アドレス（既存） |
| `MAIL_FROM_NAME` | `config('mail.from.name')` | 送信者名（既存） |
| `MAIL_ADMIN_ADDRESS` | `config('mail.admin_addresses')` | 管理者の通知先。カンマ区切りで複数指定できる |

`config/mail.php` は `MAIL_ADMIN_ADDRESS` をカンマで分割し、前後の空白を除いた配列として公開する。空の要素は取り除く。

件名のサイト名は `config('app.name')` を使う。

### メールの形式

Laravel の Markdown メール（`<x-mail::message>`）を使い、標準レイアウトをそのまま利用する。HTML 版とテキスト版が自動生成される。

### 各メールの内容

**1. 会員登録完了（会員宛）**

- 件名: `【<サイト名>】会員登録が完了しました`
- 本文: 氏名・会員ID・登録メールアドレス、マイページへのリンク

**2. お問い合わせ受付通知（管理者宛）**

- 件名: `【<サイト名>】お問い合わせを受け付けました`
- 本文: 受付日時・送信者名・メールアドレス・対象商品・本文

**3. お問い合わせ控え（送信者宛）**

- 件名: `【<サイト名>】お問い合わせを承りました`
- 本文: 3営業日以内に返信する旨、送信内容（対象商品・本文）の控え

**4. 注文受付（会員宛）**

- 件名: `【<サイト名>】ご注文を承りました（<注文番号>）`
- 本文:
  - 注文番号・注文日時
  - 明細（商品名・サイズ／カラー・単価・数量・小計）
  - 金額（商品合計・送料・代引き手数料・合計）
  - 配送先（宛名・郵便番号・住所・電話番号）
  - 支払方法・配達予定日
  - 支払方法が銀行振込のときは `orders.bank_transfer_note` を掲載する
  - マイページの注文詳細へのリンク

**5. 注文通知（管理者宛）**

- 件名: `【<サイト名>】新規注文（<注文番号>）`
- 本文: 注文番号・注文日時・顧客名と会員ID・合計金額・支払方法、管理画面の注文詳細へのリンク

明細は載せない（商品の確認は管理画面で行う）。

**6. パスワード再設定（会員宛）**

- 件名: `【<サイト名>】パスワード再設定のご案内`
- 本文: 再設定画面へのボタン、有効期限（60分）、心当たりが無ければ破棄してよい旨

**7. 出荷完了（会員宛）**

- 件名: `【<サイト名>】商品を発送しました（<注文番号>）`
- 本文: 注文番号・出荷日時・お届け先・送り状番号（入力されている場合のみ）、マイページの注文詳細へのリンク

配送業者を保持しないため、追跡サイトへのリンクは載せない。

**8. 退会完了（会員宛）**

- 件名: `【<サイト名>】退会手続きが完了しました`
- 本文: 氏名・退会が完了した旨・利用への感謝、同じメールアドレスでは再登録できないこと、注文の履歴は保持していること

退会後はログインできないため、マイページ等へのボタンは置かない。

### 主要な処理フロー

**送信の共通処理（`NotificationMailer`）**

1. 宛先が空なら何もしない（管理者アドレスが未設定の環境で管理者宛をスキップするため）。
2. `Mail::to()->send()` で同期送信する。
3. 送信に失敗した場合は例外を捕まえて `Log::error()` に記録し、呼び出し元へは伝播させない。

**会員登録**

1. 会員を作成する。
2. 会員登録完了メールを送る。

送信後の画面遷移は [docs/front/auth.md](front/auth.md) が正本。

**お問い合わせ**

1. `contacts` に1件保存する。
2. 管理者へ受付通知を送る。
3. フォームに入力されたアドレスへ控えを送る。

**注文**

1. トランザクション内で在庫を引き、注文を確定する。
2. **トランザクションの外で**、会員へ注文受付メール、管理者へ注文通知を送る。
3. 注文完了画面へリダイレクトする。

## 業務ルール

- キュー基盤を運用しないため、メールはリクエスト内で同期送信する。
- 送信の失敗は登録・注文の成立を妨げない。失敗はログにのみ記録するため、届かなかった通知に気づくにはログの確認が必要になる。
- お問い合わせの控えは、検証していないアドレス（フォームの入力値）へ送る。踏み台にされないよう、送信自体のレート制限（`throttle:10,60`）で担保する。追加の対策（アドレスの到達確認・CAPTCHA）は行わない。
- 利用者・管理者が入力した自由記述（お問い合わせの氏名・対象商品・本文、銀行振込の案内文）は Markdown 記法として解釈させず、入力どおりに表示する。解釈させると、控えメールの受け取り手に対して本文中のリンク記法で誘導先を偽装できる。
- 管理者の通知先は `.env` で管理し、管理画面からは変更できない。
- 注文のステータス変更で通知を送るのは出荷済みへの遷移時のみで、送るかどうかは管理者が操作のたびに選ぶ（[docs/admin/order-show.md](admin/order-show.md) が正本）。
- パスワード再設定メールは、申請されたアドレスの会員が存在するときだけ送る。存在しない場合も画面の応答は変えない（[docs/front/password-reset.md](front/password-reset.md) が正本）。

## 関連ドキュメント

- [docs/front/auth.md](front/auth.md) — 会員登録（登録完了メールの送信元）
- [docs/front/contact.md](front/contact.md) — お問い合わせ（受付通知・控えの送信元）
- [docs/front/checkout.md](front/checkout.md) — 購入手続き（注文確定の処理）
- [docs/front/order-complete.md](front/order-complete.md) — 注文完了（注文受付メールと同じ内容を画面にも表示する）
- [docs/admin/order-show.md](admin/order-show.md) — 管理画面の注文詳細（注文通知メールのリンク先）
- [docs/2_database.md](2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Config | `config/mail.php` |
| Service | `app/Services/Mail/NotificationMailer.php` |
| Support | `app/Support/MarkdownText.php` |
| Mailable | `app/Mail/Front/RegistrationCompleted.php` |
| Mailable | `app/Mail/Front/ContactAcknowledgement.php` |
| Mailable | `app/Mail/Front/OrderReceived.php` |
| Mailable | `app/Mail/Admin/ContactReceived.php` |
| Mailable | `app/Mail/Admin/OrderPlaced.php` |
| Mailable | `app/Mail/Front/PasswordResetLink.php` |
| Mailable | `app/Mail/Front/OrderShipped.php` |
| Mailable | `app/Mail/Front/WithdrawalCompleted.php` |
| View | `resources/views/emails/front/registration-completed.blade.php` |
| View | `resources/views/emails/front/contact-acknowledgement.blade.php` |
| View | `resources/views/emails/front/order-received.blade.php` |
| View | `resources/views/emails/admin/contact-received.blade.php` |
| View | `resources/views/emails/admin/order-placed.blade.php` |
| View | `resources/views/emails/front/password-reset-link.blade.php` |
| View | `resources/views/emails/front/order-shipped.blade.php` |
| View | `resources/views/emails/front/withdrawal-completed.blade.php` |
| Action | `app/Actions/Front/MyPage/WithdrawUser.php` |
| Model | `app/Models/User.php` |
| Action | `app/Actions/Front/Auth/RegisterUser.php` |
| Action | `app/Actions/Front/Contact/SubmitContact.php` |
| Controller | `app/Http/Controllers/Front/Auth/RegisteredUserController.php` |
| Controller | `app/Http/Controllers/Front/ContactController.php` |
| Service | `app/Services/Front/Order/PlaceOrderService.php` |
| Service | `app/Services/Admin/Order/UpdateOrderStatusService.php` |
| Test | `tests/Unit/Services/Mail/NotificationMailerTest.php` |
| Test | `tests/Feature/Mail/MailRenderingTest.php` |
| Test | `tests/Feature/Front/Auth/RegisterTest.php` |
| Test | `tests/Feature/Front/Contact/ContactStoreTest.php` |
| Test | `tests/Feature/Front/Order/PlaceOrderTest.php` |

## 受け入れ条件

- 宛先を指定すると送信され、空なら送信されない: `tests/Unit/Services/Mail/NotificationMailerTest.php`（`宛先を指定すると送信される`・`宛先が空なら送信されない`）
- 管理者の宛先が未設定なら送らず、複数指定すれば全てが宛先になる: 同上（`管理者の宛先が未設定なら送信されない`・`管理者の宛先を複数指定すると全てが宛先になる`）
- `MAIL_ADMIN_ADDRESS` のカンマ区切りが宛先の配列に解析される: 同上（`環境変数のカンマ区切りが宛先の配列に解析される`）
- 送信に失敗しても例外が伝播せずログに残る: 同上（`送信に失敗しても例外は伝播せずログに残る`）
- 8通すべてが例外なく描画でき、記載項目とリンクが揃う: `tests/Feature/Mail/MailRenderingTest.php`（各メールの描画。支払方法による振込案内・代引き手数料の出し分け、送り状番号の有無を含む）
- 自由記述が Markdown 記法として解釈されず、改行が保たれる: 同上（`問い合わせ本文のリンク記法は装飾に変換されず改行が保たれる`）
- 会員登録で登録完了メールが会員宛に送られる: `tests/Feature/Front/Auth/RegisterTest.php`（`会員登録すると登録完了メールが送られる`・`登録に失敗したときはメールが送られない`）
- お問い合わせで管理者への通知と送信者への控えが送られる: `tests/Feature/Front/Contact/ContactStoreTest.php`（`送信すると管理者への通知と送信者への控えが送られる`・`管理者の宛先が未設定でも送信者への控えは送られる`）
- 注文の確定で会員への受付メールと管理者への通知が送られ、送信に失敗しても注文が成立する: `tests/Feature/Front/Order/PlaceOrderTest.php`（`注文を確定すると会員と管理者にメールが送られる`・`メールの送信に失敗しても注文は成立する`・`注文が失敗したときはメールが送られない`）
- パスワード再設定メールが申請者へ送られる: `tests/Feature/Front/Auth/PasswordResetTest.php`（`登録済みのアドレスに再設定メールが送られる`）
- 出荷完了メールが送信を選んだときだけ送られる: `tests/Feature/Admin/Order/OrderStatusUpdateTest.php`（`送信を選ぶと会員へ出荷完了メールが送られる`・`送信を選ばなければメールは送られない`）
- 退会の完了で会員へメールが送られる: `tests/Feature/Front/MyPage/WithdrawalTest.php`（`退会すると完了メールが送られる`・`退会に失敗したときはメールが送られない`）
- 退会完了メールに氏名と感謝の文言が載る: `tests/Feature/Mail/MailRenderingTest.php`（`退会完了メールに氏名と感謝の文言が載る`）
- メールクライアントでの実際の見た目（改行位置・表の折り返し・ボタンの表示）: 自動テストなし。`MAIL_MAILER=log` でログに出力された本文を目視確認して担保する
