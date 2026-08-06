# 単位07: 管理画面 - 注文管理

依存: 単位03

注文一覧・注文詳細・ステータス更新。すべてスナップショット列のみで描画する。

## スコープ

- 注文一覧: 絞り込み（ステータス・注文番号／顧客名）・クリアボタン・件数表示・詳細導線
- 注文詳細: 注文商品・お届け先・お支払い・配達予定日・合計金額
- ステータス更新（確認モーダル付き）＋変更履歴の記録
- キャンセルへの変更時の在庫戻し

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Action） | `laravel-set:laravel` |
| クエリビルダ・トランザクション | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/orders` | `admin.orders.index` | 注文一覧 |
| GET | `/admin/orders/{order}` | `admin.orders.show` | 注文詳細 |
| PUT | `/admin/orders/{order}/status` | `admin.orders.status.update` | ステータス更新 |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/OrderController.php` | `index()` / `show()` |
| Controller | `app/Http/Controllers/Admin/OrderStatusController.php` | `update()` |
| FormRequest | `app/Http/Requests/Admin/Order/UpdateOrderStatusRequest.php` | ステータス値の検証・遷移可否の検証 |
| Service | `app/Services/Admin/Order/UpdateOrderStatusService.php` | ステータス更新・履歴記録・在庫戻しをトランザクションで処理 |
| Action | `app/Actions/Admin/Order/BuildOrderFilter.php` | 注文一覧の絞り込みクエリの組み立て |
| Action | `app/Actions/Order/RestoreStockFromOrder.php` | 注文明細の数量分だけ在庫を戻す（単位17のキャンセル依頼からも使う） |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Order/Index.tsx` | 絞り込みバー＋一覧テーブル |
| Page | `resources/js/admin/Pages/Order/Show.tsx` | 左に注文商品＋金額、右にお届け先・お支払い・ステータス更新の2カラム |
| Component | `resources/js/admin/Components/Order/StatusBadge.tsx` | ステータスバッジ（6ステータスの配色） |
| Component | `resources/js/admin/Components/Order/StatusUpdateCard.tsx` | ステータス選択 select ＋更新ボタン＋確認モーダル |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「注文管理」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
// Order/Index.tsx
type OrderRow = {
  id: number;
  order_number: string;
  ordered_at: string;          // 'YYYY.MM.DD'
  customer_name: string;       // スナップショット列
  total: number;
  payment_method_label: string;
  status: OrderStatus;
  status_label: string;
};
type Filters = { status: OrderStatus | 'all'; q: string | null };
type Props = { orders: Paginated<OrderRow>; filters: Filters; totalCount: number };

// Order/Show.tsx
type OrderItemRow = {
  product_name: string;
  variant_label: string;       // 'アクア / M' または '規格なし'
  sku_code: string | null;
  unit_price: number;
  quantity: number;
  subtotal: number;
};
type Props = {
  order: {
    order_number: string;
    ordered_at: string;
    status: OrderStatus;
    payment_method_label: string;
    items: OrderItemRow[];
    subtotal: number;
    shipping_fee: number;
    cod_fee: number;
    total: number;
    estimated_delivery_date: string;
    shipping: { recipient_name: string; postal_code: string; prefecture_name: string; city: string; address_line1: string; address_line2: string | null; tel: string };
    customer: { member_code: string; name: string; email: string; tel: string | null };
    histories: { from_status_label: string | null; to_status_label: string; admin_name: string | null; changed_at: string }[];
  };
  statusOptions: { value: OrderStatus; label: string }[];
};
```

### バリデーション

`UpdateOrderStatusRequest`:

| 項目 | ルール |
|---|---|
| `status` | 必須・`App\Enums\OrderStatus` の値のいずれか・現在のステータスと異なること・遷移が許可されていること |

### ステータス遷移の許可

| 現在 | 遷移可能先 |
|---|---|
| 注文受付 `received` | 入金待ち / 入金確認済み / 出荷準備中 / キャンセル |
| 入金待ち `awaiting_payment` | 入金確認済み / キャンセル |
| 入金確認済み `payment_confirmed` | 出荷準備中 / キャンセル |
| 出荷準備中 `preparing` | 出荷済み / キャンセル |
| 出荷済み `shipped` | （遷移不可・終端） |
| キャンセル `cancelled` | （遷移不可・終端） |

終端ステータスからの変更を試みた場合は「このステータスからは変更できません」を返す。

### 主要な処理フロー

**注文一覧**:
1. `orders` のスナップショット列のみを取得する（`users` / `products` を JOIN しない）
2. 絞り込みを適用する
   - `status`: 完全一致（`all` は条件なし）
   - `q`: `order_number` LIKE 部分一致 **OR** `customer_name` LIKE 部分一致
3. `ordered_at` の降順でページネーション（1ページ50件）
4. 件数表示は「絞り込み後の件数」

**注文詳細**: `orders` と `order_items`（＋`order_status_histories` と履歴の `admin`）を取得する。商品名・カテゴリ名・単価・SKUコードはすべて `order_items` のスナップショット列を使い、`products` を参照しない。

**ステータス更新**（`UpdateOrderStatusService`。全体を1トランザクションで囲む）:
1. 遷移の許可を検証する
2. `orders.status` を更新する。`cancelled` へ遷移する場合は `cancelled_at` に現在時刻を設定する
3. `order_status_histories` に1行追加する（`from_status` / `to_status` / `admin_id` / `changed_at`）
4. `cancelled` へ遷移する場合、`RestoreStockFromOrder` で在庫を戻す
   - `order_items` の各行について、`product_variant_id` が `null` でなければ対応する `stocks.quantity` に `quantity` を加算する
   - `product_variant_id` が `null`（商品が削除済み）の行は在庫を戻さずスキップする
5. 注文詳細へリダイレクトし「ステータスを更新しました」をフラッシュ

## 業務ルール

- 注文一覧・注文詳細は `orders` / `order_items` / `order_status_histories` のみを参照する。商品価格・商品名・会員情報・送料設定・EC基本設定が後から変更されても表示は変わらない
- 銀行振込の注文は初期ステータス「入金待ち」、代引きの注文は「注文受付」（単位16で設定する）
- キャンセルへの遷移時のみ在庫を戻す。それ以外の遷移では在庫を操作しない
- ステータス変更の通知メールは送信しない（モックのモーダル本文には「お客様へ通知メールが送信されます」とあるが、メール送信は要件のスコープ外）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Order/OrderIndexTest.php` | ステータス絞り込み／注文番号・顧客名の部分一致／組み合わせ条件／件数表示／注文日の降順／未認証のリダイレクト |
| `tests/Feature/Admin/Order/OrderShowTest.php` | 注文詳細がスナップショット列で描画されること／商品の価格・名称・カテゴリ名を変更しても表示が変わらないこと／商品を削除しても明細が表示されること |
| `tests/Feature/Admin/Order/OrderStatusUpdateTest.php` | 許可された遷移が成功すること／終端ステータス（出荷済み・キャンセル）からの変更が拒否されること／許可されていない遷移が拒否されること／同一ステータスへの更新が拒否されること／履歴が1行記録されること（`admin_id` が記録されること） |
| `tests/Feature/Admin/Order/OrderCancelStockTest.php` | キャンセルへの遷移で明細の数量分だけ在庫が戻ること／`product_variant_id` が `null` の明細はスキップされること／キャンセル以外の遷移では在庫が変わらないこと／途中で失敗すれば全件ロールバックされること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **注文キャンセル時の在庫戻し**: 要件で「引き続き検討」。推奨=**戻す**（単位01の論点3と同じ判断）。
2. **ステータス遷移の制限**: 要件はステータスの一覧だけを定めており遷移規則は未定義。推奨=上表の遷移表を適用する（出荷済み・キャンセルを終端とする）。任意のステータスへ自由に変更できるようにしたい場合は実行時に指示すること。
3. **ステータス変更履歴**: 要件に記載はないが、受注業務の追跡に必要なため推奨=**`order_status_histories` に記録し、注文詳細に表示する**。
4. **通知メール**: 送信しない（要件のスコープ外。モックのモーダル本文からは通知メールの記述を削る）。
5. **注文の編集・削除**: 実装しない（要件はステータス更新のみ）。
6. **ページネーション**: 推奨=1ページ50件。
7. **CSVエクスポート**: モックの注文管理ヘッダーにCSVボタンがあるが、要件の「CSV対応が必要な機能」表は商品・会員・管理者マスタのみ。推奨=**注文管理のCSVボタンは設置しない**。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/order-index.md`
  - `docs/admin/order-show.md`（ステータス遷移規則と在庫戻しの**正本**）
- 本計画ファイルを削除し、トラッカーの状態を更新する
