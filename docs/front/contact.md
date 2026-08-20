# フロント お問い合わせ

本ドキュメントは、送信時に記録する**対象商品（`product_id` / `product_name`）の決定規則**の正本である。

## 機能概要

- **対象画面・機能の目的:** 会員・訪問者が、商品・注文・配送についての問い合わせを送信する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/contact` | `contact` | お問い合わせフォーム |
| POST | `/contact` | `contact.store` | 送信 |

- **アクセス権限・ミドルウェア:** 認証は不要。送信（`POST`）のみ `throttle:10,60`（同一の送信元から1時間に10回まで）を適用する。
- **本ドキュメントのスコープ:** フォームの表示と送信、`contacts` への保存。管理画面での閲覧・対応状況の記録は [docs/admin/contact.md](../admin/contact.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `contacts` | 送信内容を保存する。ログイン中は `user_id` を記録し、未ログインは `null` |
| `products` | 商品詳細から遷移したときの対象商品を引き当てる（参照のみ） |

対象商品を識別する列を本機能で追加する。

| カラム | 型 | 用途 |
|---|---|---|
| `contacts.product_id` | `foreignId`・nullable・→ `products.id` set null | 商品詳細から遷移したときの対象商品。集計・絞り込みのキーとして使う参照専用の列 |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
お問い合わせ                              （h1）
商品・ご注文・配送についてのご質問を承ります。
3営業日以内にご返信いたします。

商品詳細から遷移したとき          直接アクセスしたとき
┌────────────────────────┐  ┌────────────────────────┐
│ お名前 *                │  │ お名前 *                │
│ メールアドレス *         │  │ メールアドレス *         │
│ 対象商品                │  │ 対象商品                │
│  BIANCHI OLTRE XR4     │  │ [                    ] │ ← 自由入力（任意）
│  ← 文字表示（編集不可）   │  │                        │
│ お問い合わせ内容 *       │  │ お問い合わせ内容 *       │
│ [ 送信する ]            │  │ [ 送信する ]            │
└────────────────────────┘  └────────────────────────┘
```

- 幅は最大560pxで中央寄せにする。エラーは各入力欄の下に出す。
- 対象商品が確定している場合は入力欄を置かず、商品名を文字として表示する。`product_id` は hidden で送る。
- 送信に成功するとその場に留まり（`preserveScroll`）、成功メッセージをトーストで出す。本文の入力欄だけを空に戻し、お名前・メールアドレス・対象商品は残す。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/Contact/Create.tsx
type Props = {
    defaults: {
        name: string;          // ログイン中は会員氏名、未ログインは空
        email: string;         // ログイン中は会員のメールアドレス、未ログインは空
        product_name: string;  // 対象商品が確定していないときの初期値（常に空）
    };
    // クエリ ?product_id= で公開商品を引き当てられたときのみ値が入る
    product: { id: number; name: string } | null;
};
```

送信項目は `name` / `email` / `product_id` / `product_name` / `body` の5つ。`product` が渡っているときは `product_id` を送り、`product_name` は送らない。

### 入力値バリデーションルール（`Front\Contact\StoreContactRequest`）

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `email` | 必須・メール形式・最大191 |
| `product_id` | 任意・整数 |
| `product_name` | 任意・文字列・最大255 |
| `body` | 必須・文字列・10文字以上・最大5000 |

`product_id` は存在チェック（`exists`）を課さない。存在しない・非公開の商品IDは検証エラーにせず、対象商品なしとして扱う（下記の決定規則を参照）。

### 対象商品の決定規則

`contacts.product_id` は集計・絞り込みのキーになるため、**送信された商品名を信用せず、サーバー側で商品マスタから引き直す**。

| 送信された `product_id` | 保存する `product_id` | 保存する `product_name` |
|---|---|---|
| 公開中（`is_published = true`）の商品を指す | その商品のID | その商品の `products.name` |
| 存在しない・非公開・未送信 | `null` | 送信された `product_name`（未送信なら `null`） |

- 画面上の編集不可は表示の制御にすぎず、`POST` は直接呼び出せる。`product_id` が有効な場合に送信値の `product_name` を使わないことで、集計キーと表示名の食い違いを防ぐ。
- 商品が後から削除されると `product_id` は `null` になるが、`product_name` は保存時の文字列が残る。
- クエリ `?product_name=` は受け付けない。商品名を初期値に入れる手段は `?product_id=` のみとする。

### 主要な処理フロー

**フォームの表示（`create()`）**

1. クエリ `product_id` があれば公開商品（`is_published = true`）を1件引く。引けた場合のみ `product` に `id` / `name` を入れる。引けない場合は `null` にする。
2. ログイン中なら「お名前」「メールアドレス」に会員の登録情報を初期値として入れる。

**送信（`store()` と `SubmitContact`）**

1. 入力を検証する。
2. 上記「対象商品の決定規則」に従って `product_id` と `product_name` を確定する。
3. `SubmitContact` が `contacts` に1件保存する。ログイン中なら `user_id` を記録し、未ログインなら `null` にする。`status` は既定値の `unhandled` になる（区分の正本は [docs/admin/contact.md](../admin/contact.md)）。
4. 続けて管理者へ受付通知を、フォームに入力されたアドレスへ控えを送る。送信の仕様は [docs/mail-notification.md](../mail-notification.md) が正本。
5. 元の画面へ戻して `送信しました。3営業日以内にご返信いたします。` を表示する。

## 業務ルール

- 商品詳細から遷移した問い合わせは、送信者が対象商品を書き換えられない。手入力できるのは商品を経由せず直接フォームを開いた場合のみで、その場合は `product_id` を持たないため集計の対象にならない。
- スパム対策はレート制限のみとし、CAPTCHA は導入しない。

## 関連ドキュメント

- [docs/admin/contact.md](../admin/contact.md) — 管理画面での閲覧・対応状況の記録の正本
- [docs/front/product-show.md](product-show.md) — 商品詳細の「この商品について問い合わせる」（商品IDを付けて本画面へ遷移する）
- [docs/front/static-pages.md](static-pages.md) — 買い物ガイド（返品・交換の連絡先として本画面を案内する）
- [docs/front/common-layout.md](common-layout.md) — ヘッダー・フッターのナビゲーションの正本
- [docs/mail-notification.md](../mail-notification.md) — 受付通知・控えメールの正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Migration | `database/migrations/2026_08_18_000001_add_product_and_handling_columns_to_contacts_table.php` |
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/ContactController.php` |
| Action | `app/Actions/Front/Contact/SubmitContact.php` |
| Action | `app/Actions/Front/Contact/ResolveContactProduct.php` |
| FormRequest | `app/Http/Requests/Front/Contact/StoreContactRequest.php` |
| Model | `app/Models/Contact.php` |
| Page | `resources/js/front/Pages/Contact/Create.tsx` |
| Component | `resources/js/front/Components/Product/PurchasePanel.tsx` |
| Test | `tests/Feature/Front/Contact/ContactCreateTest.php` |
| Test | `tests/Feature/Front/Contact/ContactStoreTest.php` |

## 受け入れ条件

- 未ログインでフォームを開ける: `tests/Feature/Front/Contact/ContactCreateTest.php`（`未ログインでもお問い合わせフォームを開ける`）
- ログイン中は氏名・メールアドレスが初期値に入る: 同上（`ログイン中は会員の氏名とメールアドレスが初期値に入る`）
- 未ログインで送信でき、`user_id` が `null` で保存される: `tests/Feature/Front/Contact/ContactStoreTest.php`（`未ログインでもお問い合わせを送信できる`）
- ログイン中は `user_id` が記録される: 同上（`ログイン中は会員が記録される`）
- 対象商品を省略できる: 同上（`対象商品は省略できる`）
- 必須・メール形式・本文の文字数を検証する: 同上（`必須項目が未入力なら送信されない`・`メールアドレスの形式が誤っていれば送信されない`・`本文が九文字なら送信されない`・`本文が十文字なら送信できる`・`本文が上限を超えると送信されない`）
- 連投がレート制限で拒否される: 同上（`同一の送信元からの連投は制限される`）
- 管理者への通知と送信者への控えが送られる: 同上（`送信すると管理者への通知と送信者への控えが送られる`・`管理者の宛先が未設定でも送信者への控えは送られる`）

以下は `product_id` 対応で追加する条件で、上表のテストファイルに実装時に追加する。

- クエリの `product_id` で公開商品を引き当て、商品名が渡る: Featureテスト
- 存在しない `product_id`・非公開商品の `product_id` では `product` が `null` になり、フォームは通常どおり開ける: Featureテスト
- `product_id` に文字列や配列を渡してもフォームを開ける: Featureテスト
- 有効な `product_id` を送ると `product_id` と商品マスタの商品名が保存される: Featureテスト
- 有効な `product_id` と異なる `product_name` を同時に送っても、保存されるのは商品マスタの商品名になる: Featureテスト
- 無効な `product_id` を送ると `product_id` は `null` になり、送信された `product_name` が保存される: Featureテスト
- `product_id` を送らず `product_name` だけを送ると、手入力の商品名として保存される: Featureテスト
- 商品詳細の「この商品について問い合わせる」が `product_id` 付きのURLへ遷移する: Featureテスト
- 対象商品が確定しているとき入力欄ではなく文字として表示されること: 目視確認
- 送信後に本文だけが空に戻ること・トーストの表示: 目視確認
