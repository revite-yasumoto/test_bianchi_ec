# フロント お問い合わせ

本ドキュメントは、送信時に記録する**対象商品（`product_id` / `product_name` / `product_code`）の決定規則**と**問い合わせ番号の採番規則**の正本である。

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

対象商品を識別する列と問い合わせ番号の列を本機能で追加する。

| カラム | 型 | 用途 |
|---|---|---|
| `contacts.contact_number` | `string(20)`・unique | 問い合わせ番号（`INQ-YYMM-NNNN`）。管理画面・CSV・メールで問い合わせを一意に指す |
| `contacts.product_id` | `foreignId`・nullable・→ `products.id` set null | 商品詳細から遷移したときの対象商品。集計・絞り込みのキーとして使う参照専用の列 |
| `contacts.product_code` | `string(50)`・nullable | 対象商品の商品識別コード。商品が削除された後も残す |

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
- `?product_id=` 付きのURLが商品数ぶん到達可能になるため、正規URLとして `/contact` を `<link rel="canonical">` で示す。
- 対象商品が確定している場合は入力欄を置かず、商品名を文字として表示する。`product_id` は入力欄を持たずに送信データへ含める。
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

`product_id` は存在チェック（`exists`）を課さない。存在しない・非公開の商品IDは検証エラーにせず、対象商品なしとして扱う（下記の決定規則を参照）。整数として解釈できない値のみ `integer` で弾き、この場合は送信自体が検証エラーになる。

### 対象商品の決定規則

`contacts.product_id` は集計・絞り込みのキーになるため、**送信された商品名を信用せず、サーバー側で商品マスタから引き直す**。

| 送信された `product_id` | 保存する `product_id` | 保存する `product_name` | 保存する `product_code` |
|---|---|---|---|
| 公開中（`is_published = true`）の商品を指す | その商品のID | その商品の `products.name` | その商品の `products.product_code` |
| 存在しない・非公開・未送信 | `null` | 送信された `product_name`（未送信なら `null`） | `null` |

- 画面上の編集不可は表示の制御にすぎず、`POST` は直接呼び出せる。`product_id` が有効な場合に送信値の `product_name` を使わないことで、集計キーと表示名の食い違いを防ぐ。
- 商品が後から削除されると `product_id` は `null` になるが、`product_name` と `product_code` は保存時の値が残る。`product_code` が残っていることで、削除済みの商品を指していた問い合わせと、商品名を手入力しただけの問い合わせを区別できる。
- クエリ `?product_name=` は受け付けない。商品名を初期値に入れる手段は `?product_id=` のみとする。

### 問い合わせ番号の採番規則

`INQ-YYMM-NNNN` 形式で、`YYMM` は送信月、`NNNN` は当月内の連番（4桁ゼロ埋め）。注文番号（[docs/order-snapshot.md](../order-snapshot.md) の `BNC-YYMM-NNNN`）と同じ体系で、接頭辞のみ異なる。

- **採番はトランザクション内で当月の最大連番を排他ロックしてから求める**（`SELECT ... FOR UPDATE` 相当）。件数を数えて +1 する方式では同時送信で衝突しうるため採らない。テスト用のSQLiteはロック構文を解釈しないため、同時送信の排他は自動テストで担保できない（MySQL実環境での確認が必要）。
- 番号を採番できないことを理由に送信を失敗させない。
- 既存行には、マイグレーションで受信月と月内の並び順から同じ形式の番号を振る（主キー順に走査する。受信日時は主キーと同じ順に増える）。番号を振ってから NOT NULL と一意制約を付ける。
- 連番は4桁（月あたり9999件）を上限とする。最大値の判定が文字列の辞書順によるため、これを超えると採番できない。

### 主要な処理フロー

**フォームの表示（`create()`）**

1. クエリ `product_id` があれば公開商品（`is_published = true`）を1件引く。引けた場合のみ `product` に `id` / `name` を入れる。引けない場合は `null` にする。
2. ログイン中なら「お名前」「メールアドレス」に会員の登録情報を初期値として入れる。

**送信（`store()` と `SubmitContact`）**

1. 入力を検証する。
2. 上記「対象商品の決定規則」に従って `product_id` / `product_name` / `product_code` を確定する。
3. `SubmitContact` が問い合わせ番号を採番し、`contacts` に1件保存する。ログイン中なら `user_id` を記録し、未ログインなら `null` にする。`status` は既定値の `unhandled` になる（区分の正本は [docs/admin/contact.md](../admin/contact.md)）。
4. 続けて管理者へ受付通知を、フォームに入力されたアドレスへ控えを送る。どちらの本文にも問い合わせ番号を載せる。送信の仕様は [docs/mail-notification.md](../mail-notification.md) が正本。
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
| Action | `app/Actions/Front/Contact/GenerateContactNumber.php` |
| FormRequest | `app/Http/Requests/Front/Contact/StoreContactRequest.php` |
| Model | `app/Models/Contact.php` |
| Page | `resources/js/front/Pages/Contact/Create.tsx` |
| Component | `resources/js/front/Components/Product/PurchasePanel.tsx` |
| Test | `tests/Feature/Front/Contact/ContactCreateTest.php` |
| Test | `tests/Feature/Front/Contact/ContactStoreTest.php` |
| Test | `tests/Unit/GenerateContactNumberActionTest.php` |

## 受け入れ条件

- 未ログインでフォームを開ける: `tests/Feature/Front/Contact/ContactCreateTest.php`（`未ログインでもお問い合わせフォームを開ける`）
- ログイン中は氏名・メールアドレスが初期値に入る: 同上（`ログイン中は会員の氏名とメールアドレスが初期値に入る`）
- 未ログインで送信でき、`user_id` が `null` で保存される: `tests/Feature/Front/Contact/ContactStoreTest.php`（`未ログインでもお問い合わせを送信できる`）
- ログイン中は `user_id` が記録される: 同上（`ログイン中は会員が記録される`）
- 対象商品を省略できる: 同上（`対象商品は省略できる`）
- 必須・メール形式・本文の文字数を検証する: 同上（`必須項目が未入力なら送信されない`・`メールアドレスの形式が誤っていれば送信されない`・`本文が九文字なら送信されない`・`本文が十文字なら送信できる`・`本文が上限を超えると送信されない`）
- エラーメッセージが日本語で返る（翻訳キーが露出しない。翻訳の正本は [docs/front/common-layout.md](common-layout.md)）: 同上（`未入力のエラーメッセージが日本語で返る`・`文字数不足のエラーメッセージが日本語で返る`）
- 連投がレート制限で拒否される: 同上（`同一の送信元からの連投は制限される`）
- 管理者への通知と送信者への控えが送られる: 同上（`送信すると管理者への通知と送信者への控えが送られる`・`管理者の宛先が未設定でも送信者への控えは送られる`）
- クエリの `product_id` で公開商品を引き当て、商品名が渡る: `tests/Feature/Front/Contact/ContactCreateTest.php`（`クエリの商品の指定で公開商品を引き当てる`）
- 存在しない `product_id`・非公開商品の `product_id` では `product` が `null` になり、フォームは通常どおり開ける: 同上（`非公開商品を指定したときは対象商品なしとして開ける`・`存在しない商品を指定したときは対象商品なしとして開ける`）
- `product_id` に文字列や配列を渡してもフォームを開ける: 同上（`商品の指定に文字列や配列を渡してもフォームを開ける`）
- 有効な `product_id` を送ると `product_id` と商品マスタの商品名が保存される: `tests/Feature/Front/Contact/ContactStoreTest.php`（`有効な商品を指定すると商品マスタの商品名で保存される`）
- 有効な `product_id` と異なる `product_name` を同時に送っても、保存されるのは商品マスタの商品名になる: 同上（`有効な商品の指定と異なる商品名を送っても商品マスタの商品名で保存される`）
- 無効な `product_id` を送ると `product_id` は `null` になり、送信された `product_name` が保存される: 同上（`非公開商品を指定すると対象商品なしとして保存される`・`存在しない商品を指定すると対象商品なしとして保存される`）
- `product_id` を送らず `product_name` だけを送ると、手入力の商品名として保存される: 同上（`商品を指定しなければ手入力の商品名が保存される`）
- 送信直後のステータスが未対応になる: 同上（`送信直後のステータスは未対応になる`）
- 送信時に問い合わせ番号が採番され、続けて送信すると連番が振られる: 同上（`送信時に問い合わせ番号が採番される`・`続けて送信すると連番が振られる`）
- 番号が当月内の連番になり、月が変わると1に戻る・欠番があっても最大の次を採る: `tests/Unit/GenerateContactNumberActionTest.php`（全6件）
- 有効な商品を指定すると商品識別コードも保存され、指定しなければ空になる: `tests/Feature/Front/Contact/ContactStoreTest.php`（`有効な商品を指定すると商品識別コードも保存される`・`商品を指定しなければ商品識別コードは空になる`）
- 同時送信で番号が衝突しないこと: MySQL実環境での確認（テスト用のSQLiteはロック構文を解釈しない）
- 商品詳細の「この商品について問い合わせる」が `product_id` 付きのURLへ遷移する: 目視確認（リンクの生成はフロント側の実装のため）
- 対象商品が確定しているとき入力欄ではなく文字として表示されること: 目視確認
- 送信後に本文だけが空に戻ること・トーストの表示: 目視確認
