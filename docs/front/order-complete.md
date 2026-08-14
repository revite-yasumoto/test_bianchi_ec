# フロント 注文完了

## 機能概要

- **対象画面・機能の目的:** 注文が確定したことと、注文番号・配達予定日・銀行振込の案内を提示する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/orders/{order}/complete` | `orders.complete` | 注文完了 |

- **アクセス権限・ミドルウェア:** `auth`（`web` ガード）で保護し、`OrderPolicy::view` を `can` ミドルウェアで適用する。自分以外の注文は403。
- **本ドキュメントのスコープ:** 注文完了画面の表示とアクセス制御。注文を確定する処理は [docs/front/checkout.md](checkout.md)、保存済みの値の出所は [docs/order-snapshot.md](../order-snapshot.md) が正本。

## 使用テーブル

`orders` を参照する（`order_number` / `estimated_delivery_date` / `payment_method` / `bank_transfer_note`）。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+-----------------------------------------+
|            (チェックアイコン)             |
|      ご注文ありがとうございました         |
|  ご注文を受け付けました。ステータスは     |
|  「入金待ち」です。…                     |
|  +-----------------------------------+  |
|  | 注文番号                           |  |
|  | BNC-2608-0042                     |  |
|  | お届け予定日 8月16日（土）          |  |
|  +-----------------------------------+  |
|  +-----------------------------------+  |
|  | お振込みのご案内                    |  |
|  | （注文時点の振込案内文）            |  |
|  +-----------------------------------+  |
|  [ 注文履歴を見る ]  [ TOPへ戻る ]      |
+-----------------------------------------+
```

- 完了メッセージは支払い方法で切り替える。代引きは「ご注文を受け付けました。出荷準備が整い次第、発送のご案内をお送りします。」、銀行振込は「ご注文を受け付けました。ステータスは「入金待ち」です。ご入金確認後に発送準備を開始します。」。
- 「お振込みのご案内」は `orders.bank_transfer_note` がある場合（＝銀行振込の注文）のみ表示し、改行を保持して出す。
- 「注文履歴を見る」はマイページの注文履歴（`mypage.index`）へ遷移する。

## インターフェース ＆ データロジック

- **Props:**

```ts
type Props = {
    order: {
        order_number: string;
        estimated_delivery_date: string;   // ISO 8601 の日付
        payment_method: PaymentMethod;
        bank_transfer_note: string | null;
    };
};
```

配達予定日は ISO 8601 の日付で渡し、`8月16日（土）` への整形はフロント側（`japaneseDateLabel`）で行う。

- **入力値バリデーションルール:** なし（表示のみ）。

- **主要な処理フロー:**
1. ルートモデルバインディングで注文を取得し、`OrderPolicy::view` で自分の注文であることを確認する（他人の注文は403）。
2. 表示に使う4項目だけを渡す。
3. リロードしても同じ内容を表示する（注文はDBに確定済みで、注文IDをURLに持つため再注文にならない）。

## 業務ルール

- 振込先の案内はこの画面と注文詳細に表示するほか、注文の確定時に送る受付メールにも載せる（[docs/mail-notification.md](../mail-notification.md) が正本）。

## 関連ドキュメント

- [docs/front/checkout.md](checkout.md) — 注文確定処理と、この画面へ遷移する導線の正本
- [docs/order-snapshot.md](../order-snapshot.md) — 表示する値の出所の正本
- [docs/front/mypage-order.md](mypage-order.md) — 「注文履歴を見る」の遷移先と、`OrderPolicy` のキャンセル認可の正本
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout` の正本
- [docs/admin/ec-setting.md](../admin/ec-setting.md) — 銀行振込の案内文の設定元
- [docs/mail-notification.md](../mail-notification.md) — 注文受付メールの正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/OrderController.php` |
| Policy | `app/Policies/OrderPolicy.php` |
| Page | `resources/js/front/Pages/Order/Complete.tsx` |
| Util | `resources/js/front/lib/delivery.ts` |
| Test | `tests/Feature/Front/Order/OrderCompleteTest.php` |

## 受け入れ条件

- 未ログインではログイン画面へ遷移する: `tests/Feature/Front/Order/OrderCompleteTest.php`（`未ログインではログイン画面へリダイレクトされる`）
- 自分の注文完了画面が注文番号・配達予定日とともに表示される: 同上（`自分の注文完了画面が表示される`）
- 他人の注文完了画面は403になる: 同上（`他人の注文完了画面は表示できない`）
- 銀行振込の注文には振込案内文が渡り、代引きには渡らない: 同上（`銀行振込の注文には振込案内文が渡される`・`代引きの注文には振込案内文が渡されない`）
- リロードしても表示でき、注文が増えない: 同上（`リロードしても表示できて注文が増えない`）
- 完了メッセージが支払い方法で切り替わる: 自動テストなし。目視確認で担保する
- Props型定義の整合性: `npx tsc --noEmit`
