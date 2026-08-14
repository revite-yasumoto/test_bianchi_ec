# フロント お問い合わせ

## 機能概要

- **対象画面・機能の目的:** 会員・訪問者が、商品・注文・配送についての問い合わせを送信する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/contact` | `contact` | お問い合わせフォーム |
| POST | `/contact` | `contact.store` | 送信 |

- **アクセス権限・ミドルウェア:** 認証は不要。送信（`POST`）のみ `throttle:10,60`（同一の送信元から1時間に10回まで）を適用する。
- **本ドキュメントのスコープ:** フォームの表示と送信、`contacts` への保存。送信内容の閲覧・返信機能は実装しない。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `contacts` | 送信内容を保存する。ログイン中は `user_id` を記録し、未ログインは `null` |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
お問い合わせ                              （h1）
商品・ご注文・配送についてのご質問を承ります。
3営業日以内にご返信いたします。

┌────────────────────────┐
│ お名前 *                │
│ メールアドレス *         │
│ 対象商品                │  ← 商品詳細から遷移したときは商品名が入る
│ お問い合わせ内容 *       │  ← 7行のテキストエリア
│ [ 送信する ]            │
└────────────────────────┘
```

- 幅は最大560pxで中央寄せにする。エラーは各入力欄の下に出す。
- 送信に成功するとその場に留まり（`preserveScroll`）、成功メッセージをトーストで出す。本文の入力欄だけを空に戻し、お名前・メールアドレス・対象商品は残す。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/Contact/Create.tsx
type Props = {
    defaults: {
        name: string;          // ログイン中は会員氏名、未ログインは空
        email: string;         // ログイン中は会員のメールアドレス、未ログインは空
        product_name: string;  // クエリ ?product_name= の値（255文字で切る）
    };
};
```

送信項目は `name` / `email` / `product_name` / `body` の4つ。

### 入力値バリデーションルール（`Front\Contact\StoreContactRequest`）

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `email` | 必須・メール形式・最大191 |
| `product_name` | 任意・文字列・最大255 |
| `body` | 必須・文字列・10文字以上・最大5000 |

### 主要な処理フロー

**フォームの表示**

1. クエリ `product_name` があれば「対象商品」の初期値に入れる。任意長のクエリが入りうるため、入力欄と同じ上限（255文字）で切る。
2. ログイン中なら「お名前」「メールアドレス」に会員の登録情報を初期値として入れる。

**送信**

1. 入力を検証する。
2. `SubmitContact` が `contacts` に1件保存する。ログイン中なら `user_id` を記録し、未ログインなら `null` にする。
3. 続けて管理者へ受付通知を、フォームに入力されたアドレスへ控えを送る。送信の仕様は [docs/mail-notification.md](../mail-notification.md) が正本。
4. 元の画面へ戻して `送信しました。3営業日以内にご返信いたします。` を表示する。

## 業務ルール

- 管理画面での閲覧機能は実装しない。内容の確認は管理者へ送る通知メール、またはデータベースの直接参照で行う。
- スパム対策はレート制限のみとし、CAPTCHA は導入しない。

## 関連ドキュメント

- [docs/front/product-show.md](product-show.md) — 商品詳細の「この商品について問い合わせる」（商品名を付けて本画面へ遷移する）
- [docs/front/static-pages.md](static-pages.md) — 買い物ガイド（返品・交換の連絡先として本画面を案内する）
- [docs/front/common-layout.md](common-layout.md) — ヘッダー・フッターのナビゲーションの正本
- [docs/mail-notification.md](../mail-notification.md) — 受付通知・控えメールの正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/ContactController.php` |
| Action | `app/Actions/Front/Contact/SubmitContact.php` |
| FormRequest | `app/Http/Requests/Front/Contact/StoreContactRequest.php` |
| Model | `app/Models/Contact.php` |
| Page | `resources/js/front/Pages/Contact/Create.tsx` |
| Test | `tests/Feature/Front/Contact/ContactCreateTest.php` |
| Test | `tests/Feature/Front/Contact/ContactStoreTest.php` |

## 受け入れ条件

- 未ログインでフォームを開ける: `tests/Feature/Front/Contact/ContactCreateTest.php`（`未ログインでもお問い合わせフォームを開ける`）
- ログイン中は氏名・メールアドレスが初期値に入る: 同上（`ログイン中は会員の氏名とメールアドレスが初期値に入る`）
- クエリの商品名が対象商品の初期値に入る: 同上（`クエリの商品名が対象商品の初期値に入る`・`上限を超える商品名は切り詰められる`）
- 未ログインで送信でき、`user_id` が `null` で保存される: `tests/Feature/Front/Contact/ContactStoreTest.php`（`未ログインでもお問い合わせを送信できる`）
- ログイン中は `user_id` が記録される: 同上（`ログイン中は会員が記録される`）
- 対象商品を省略できる: 同上（`対象商品は省略できる`）
- 必須・メール形式・本文の文字数を検証する: 同上（`必須項目が未入力なら送信されない`・`メールアドレスの形式が誤っていれば送信されない`・`本文が九文字なら送信されない`・`本文が十文字なら送信できる`・`本文が上限を超えると送信されない`）
- 連投がレート制限で拒否される: 同上（`同一の送信元からの連投は制限される`）
- 管理者への通知と送信者への控えが送られる: 同上（`送信すると管理者への通知と送信者への控えが送られる`・`管理者の宛先が未設定でも送信者への控えは送られる`）
- 送信後に本文だけが空に戻ること・トーストの表示: 自動テストなし。目視確認で担保する
