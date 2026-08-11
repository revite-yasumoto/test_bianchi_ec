# 注文詳細・ステータス更新

本ドキュメントは**ステータス遷移規則**と**キャンセル時の在庫戻し**の正本。単位16（購入手続き）・単位17（マイページのキャンセル依頼）の仕様書は、これらの規則を再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** 注文内容の確認と、ステータスの更新。キャンセルへの変更時は在庫を戻す。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/orders/{order}` | `admin.orders.show` | `auth:admin` |
| PUT | `/admin/orders/{order}/status` | `admin.orders.status.update` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。
- **本ドキュメントのスコープ:** 注文詳細の表示、ステータス更新、変更履歴の記録、キャンセル時の在庫戻し。一覧・絞り込みは [docs/admin/order-index.md](order-index.md) が正本。

## 使用テーブル

`orders` / `order_items` / `order_status_histories`（＋履歴の `admins`）を参照し、`orders` と `order_status_histories` に書き込む。キャンセル時のみ `stocks` を更新する。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
← 注文一覧へ
[出荷準備中] 注文日時 2026.07.24 10:30

+---------------------------+  +---------------------------+
| 注文商品                   |  | お届け先                   |
| チームジャージ 2026        |  | 山田 太郎                  |
|  ネイビー / M / JS-2026-11 |  | 〒150-0001                 |
|  ¥9,000          ×2 ¥18,000|  | 東京都渋谷区…              |
|---------------------------|  | お支払い・配送             |
| 商品合計         ¥18,000  |  | 銀行振込（前払い）          |
| 送料                ¥500  |  | お届け予定日 2026.07.28    |
| 代引き手数料        ¥330  |  | ご注文者                   |
| 合計            ¥18,830   |  | M-100238 / 山田 太郎 …     |
+---------------------------+  +---------------------------+
                               | ステータス更新             |
                               | [出荷済み        ▼]        |
                               | [ ステータスを更新 ]        |
                               +---------------------------+
                               | ステータス変更履歴          |
                               | 入金確認済み → 出荷準備中   |
                               |  2026.07.25 10:00 / 管理者名|
                               +---------------------------+
```

- 代引き手数料は 0 円のときは行を表示しない。
- ステータスの選択肢は現在のステータスから遷移可能なものだけを出す。遷移先が無い（最終ステータス）場合は select とボタンを出さず、その旨の案内文を表示する。
- 更新は確認モーダル（`ConfirmDialog`）を経て実行し、完了後にトーストを表示する。
- 変更履歴は新しい順に並べ、管理者名が無い履歴（会員のキャンセル依頼）は「会員による操作」と表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type OrderItemRow = {
    id: number;
    product_name: string;
    category_name: string;
    variant_label: string;   // 'ネイビー / M' または '規格なし'
    sku_code: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
};

type Props = {
    order: {
        id: number;
        order_number: string;
        ordered_at: string;              // 'YYYY.MM.DD HH:mm'
        status: string;
        status_label: string;
        status_tone: Tone;
        payment_method_label: string;
        items: OrderItemRow[];
        subtotal: number;
        shipping_fee: number;
        cod_fee: number;
        total: number;
        estimated_delivery_date: string; // 'YYYY.MM.DD'
        bank_transfer_note: string | null;
        shipping: { recipient_name; postal_code; prefecture_name; city; address_line1; address_line2; tel };
        customer: { member_code; name; email; tel };
        histories: { id; from_status_label; to_status_label; admin_name; changed_at }[];
    };
    statusOptions: { value: string; label: string }[];  // 遷移可能な先のみ
    errors: Record<string, string>;
};
```

- **入力値バリデーションルール（`Admin\Order\UpdateOrderStatusRequest`）:**

| 項目 | ルール |
|---|---|
| `status` | 必須・`App\Enums\OrderStatus` の値のいずれか・現在と異なること・遷移が許可されていること |

遷移の可否は `OrderStatus::canTransitionTo()` が単一情報源で、FormRequest はそれを呼ぶだけにする。

### ステータス遷移規則

| 現在 | 遷移可能先 |
|---|---|
| 注文受付 `received` | 入金待ち / 入金確認済み / 出荷準備中 / キャンセル |
| 入金待ち `awaiting_payment` | 入金確認済み / キャンセル |
| 入金確認済み `payment_confirmed` | 出荷準備中 / キャンセル |
| 出荷準備中 `preparing` | 出荷済み / キャンセル |
| 出荷済み `shipped` | なし（最終） |
| キャンセル `cancelled` | なし（最終） |

最終ステータスからの変更を試みた場合は「このステータスからは変更できません。」を返す。許可されていない遷移は「「〈現在〉」から「〈変更先〉」へは変更できません。」を返す。

- **主要な処理フロー:**

**詳細（`show()`）:** `orders` と `order_items`・`order_status_histories`（＋履歴の管理者）を取得し、商品名・カテゴリ名・単価・SKUコードはすべて `order_items` のスナップショット列から組み立てる。`products` は参照しない。

**ステータス更新（`UpdateOrderStatusService`。全体を1トランザクションで囲む）:**
1. `orders.status` を更新する。`cancelled` へ遷移する場合は `cancelled_at` に現在時刻を設定する。
2. `order_status_histories` に1行追加する（`from_status` / `to_status` / `admin_id` / `changed_at`）。
3. `cancelled` へ遷移する場合、`RestoreStockFromOrder` で在庫を戻す。

### キャンセル時の在庫戻し（`RestoreStockFromOrder`）

- `order_items` のうち `product_variant_id` が `null` でない行について、対応する `stocks.quantity` に明細の `quantity` を加算する。
- 商品が削除済みで `product_variant_id` が `null` の行は戻す先が無いためスキップする。
- 会員のキャンセル依頼（単位17）からも同じ Action を使う。

## 業務ルール

- 注文詳細は `orders` / `order_items` / `order_status_histories` のみを参照する。商品価格・商品名・カテゴリ名・会員情報・送料設定・EC基本設定が後から変更・削除されても表示は変わらない。
- 在庫を戻すのはキャンセルへの遷移時のみ。それ以外の遷移では在庫を操作しない。
- ステータス変更の通知メールは送信しない（要件のスコープ外）。
- 注文の編集・削除は実装しない。

## 関連ドキュメント

- [docs/admin/order-index.md](order-index.md) — 注文一覧の正本
- [docs/admin/stock.md](stock.md) — 在庫マスタの正本。手動調整の画面
- [docs/admin/auth.md](auth.md) — ログイン後の遷移先が注文一覧になる条件
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`ConfirmDialog` の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/OrderController.php` |
| Controller | `app/Http/Controllers/Admin/OrderStatusController.php` |
| FormRequest | `app/Http/Requests/Admin/Order/UpdateOrderStatusRequest.php` |
| Service | `app/Services/Admin/Order/UpdateOrderStatusService.php` |
| Action | `app/Actions/Order/RestoreStockFromOrder.php` |
| Enum | `app/Enums/OrderStatus.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Order/Show.tsx` |
| Component | `resources/js/admin/Components/Order/StatusBadge.tsx` |
| Component | `resources/js/admin/Components/Order/StatusUpdateCard.tsx` |
| Test | `tests/Feature/Admin/Order/OrderShowTest.php` |
| Test | `tests/Feature/Admin/Order/OrderStatusUpdateTest.php` |
| Test | `tests/Feature/Admin/Order/OrderCancelStockTest.php` |

## 受け入れ条件

- 詳細がスナップショット列で表示される: `tests/Feature/Admin/Order/OrderShowTest.php`（`注文詳細がスナップショット列で表示される`）
- 規格のない明細が「規格なし」と表示される: 同上（`規格のない明細は規格なしと表示される`）
- 商品の名称・価格・カテゴリ名を変更しても表示が変わらない: 同上（`商品の名称や価格を変更しても注文詳細の表示は変わらない`）
- 商品を削除しても明細が表示される: 同上（`商品を削除しても注文明細は表示される`）
- 変更履歴が新しい順に表示される: 同上（`ステータス変更履歴が新しい順で表示される`）
- 遷移可能な先だけが選択肢になり、最終ステータスでは空になる: 同上（`遷移できるステータスだけが選択肢になる`・`最終ステータスの注文には選択肢がない`）
- 未認証は詳細を開けない: 同上（`未認証は注文詳細を開けない`）
- 許可された遷移で更新できる: `tests/Feature/Admin/Order/OrderStatusUpdateTest.php`（`許可された遷移でステータスを更新できる`）
- 更新時に履歴が1行記録され、管理者IDが入る: 同上（`更新時に変更履歴が1行記録される`）
- キャンセルへの更新で `cancelled_at` が記録される: 同上（`キャンセルへの更新でキャンセル日時が記録される`）
- 最終ステータスからの変更・許可されていない遷移・同一ステータス・不正値が拒否される: 同上（`出荷済みからは変更できない`・`キャンセルからは変更できない`・`許可されていない遷移は拒否される`・`同じステータスへの更新は拒否される`・`存在しないステータス値は拒否される`）
- 未認証は更新できない: 同上（`未認証はステータスを更新できない`）
- キャンセルで明細の数量分だけ在庫が戻る: `tests/Feature/Admin/Order/OrderCancelStockTest.php`（`キャンセルへの更新で明細の数量分だけ在庫が戻る`・`複数明細でもそれぞれの在庫が戻る`）
- 商品削除済みの明細はスキップされる: 同上（`商品が削除済みの明細は在庫を戻さずスキップされる`）
- キャンセル以外の遷移では在庫が変わらない: 同上（`キャンセル以外の遷移では在庫が変わらない`）
- 遷移が拒否された場合は在庫も履歴も変わらない: 同上（`遷移が拒否された場合は在庫もステータスも変わらない`）
- 確認モーダルとトースト表示: 自動テストなし。目視確認で担保する
- 2カラムのレスポンシブ表示: 自動テストなし。目視確認で担保する
