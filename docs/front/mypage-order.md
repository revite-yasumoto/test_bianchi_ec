# フロント マイページ 注文履歴・注文詳細・キャンセル

本ドキュメントは**マイページ共通レイアウト（見出し・会員情報・タブナビゲーション）**の正本である。マイページの他の仕様書（[docs/front/mypage-address.md](mypage-address.md)・[docs/front/mypage-profile.md](mypage-profile.md)）はレイアウトを再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** 会員が自分の注文の履歴・内容を確認し、発送前の注文をキャンセルする。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/mypage` | `mypage.index` | 注文履歴（マイページの既定タブ） |
| GET | `/mypage/orders/{order}` | `mypage.orders.show` | 注文詳細 |
| POST | `/mypage/orders/{order}/cancel` | `mypage.orders.cancel` | キャンセル |

- **アクセス権限・ミドルウェア:** `auth`（`web` ガード）。注文詳細は `OrderPolicy::view`、キャンセルは `OrderPolicy::cancel` を `can` ミドルウェアで適用する。いずれも他人の注文は403。
- **本ドキュメントのスコープ:** マイページ共通レイアウトと、注文履歴・注文詳細・キャンセルの3機能。表示する注文時スナップショットの値の出所は [docs/order-snapshot.md](../order-snapshot.md)、ステータス遷移規則と在庫戻しは [docs/admin/order-show.md](../admin/order-show.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `orders` | 注文のスナップショット。キャンセル時に `status` / `cancelled_at` を更新する |
| `order_items` | 明細のスナップショット |
| `order_status_histories` | ステータス変更履歴。会員によるキャンセルは `admin_id = null` で記録する |
| `stocks` | キャンセル時に明細の数量分だけ戻す |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

### マイページ共通レイアウト

```
MY PAGE                                   （領域名）
注文履歴                                  （h1。ページの主題）
架空 太郎 様  M-000001                    （会員名・会員コード）

PC（lg以上）                        SP（lg未満）
┌──────────┬──────────────┐        ┌──────────────────────┐
│ 注文履歴  │              │        │ [注文履歴][お気に入り]… │ ← 横スクロールのタブ
│ お気に入り│  各タブの内容 │        ├──────────────────────┤
│ 配送先住所│              │        │      各タブの内容      │
│ 会員情報変更│            │        └──────────────────────┘
│ パスワード変更│          │
└──────────┴──────────────┘
```

- `h1` にはページの主題（タブ名。注文詳細では注文番号）を置き、領域名の「MY PAGE」はその上の小さな見出しで示す。各ページはレイアウトに `heading`（`h1` の文言）と `description`（`meta name="description"`）を渡す。
- タブは5項目（注文履歴・お気に入り・配送先住所・会員情報変更・パスワード変更）で、それぞれ独立したURLを持つ。現在のタブはブランド色で塗り、`aria-current="page"` を付ける。
- 注文詳細（`mypage.orders.show`）を開いている間も「注文履歴」タブを現在地として扱う。
- PCは左タブ・右コンテンツの2カラム、SPは横スクロールのタブを上に置く1カラムにする。

### 注文履歴

```
┌──────────────────────────────────────┐
│ [入金待ち] BNC-2608-0001  2026.08.13   │
│ 架空ジャージ ほか2点                    │
│ ¥45,800                                │
│ [ 注文詳細 ] [ キャンセル依頼 ]         │  ← キャンセルは可能なステータスのみ
└──────────────────────────────────────┘
（1ページ10件。以降はページ送り）
```

注文が0件のときは「ご注文はまだありません」と商品一覧への導線を出す。

### 注文詳細

```
← 注文履歴へ
[入金待ち] 2026.08.13 14:30              （h1 は「注文 BNC-2608-0001」）

┌──── ご注文商品 ────┐  ┌──── お届け先 ────┐
│ 画像 商品名          │  │ 宛名・住所・電話    │
│      規格 ／ 単価×数量│  │ お支払い・配送      │
│                 小計 │  └────────────────┘
│ 商品合計／送料／代引き│  ┌── お振込みのご案内 ─┐ ← 銀行振込のみ
│ 合計                 │  └────────────────┘
└────────────────────┘  ┌── ご注文の状況 ────┐
                          │ ステータス／日時    │
                          └────────────────┘
                          [ キャンセル依頼 ]
```

- 各カードの見出し（ご注文商品・お届け先・お支払い・配送・お振込みのご案内・ご注文の状況）は `h2` に置く。
- 明細のサムネイルは、画像が未登録でもカテゴリ相応のシルエットを出す（商品識別コードは添えない）。
- 金額内訳の「代引き手数料」は `cod_fee > 0` のときだけ出す。
- キャンセルボタンは確認モーダルを挟む。モーダルには注文番号と「元に戻せない」旨を出す。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Components/MyPage/OrderCard.tsx
export type OrderSummary = {
    id: number;
    order_number: string;
    ordered_at: string;          // ISO 8601 の日時
    status_label: string;
    status_tone: Tone;           // { fg, bg }。OrderStatus::color() 由来
    total: number;
    items_summary: string;       // 例: 架空ジャージ ほか2点
    is_cancelable: boolean;
};

// resources/js/front/Pages/MyPage/Orders.tsx
type Props = { orders: Paginated<OrderSummary> };

// resources/js/front/Pages/MyPage/OrderShow.tsx
type Props = {
    order: {
        id: number;
        order_number: string;
        ordered_at: string;              // ISO 8601 の日時
        status_label: string;
        status_tone: Tone;
        payment_method_label: string;
        bank_transfer_note: string | null;
        estimated_delivery_date: string; // ISO 8601 の日付
        items: {
            id: number;
            product_name: string;
            category_name: string;       // 画像未登録時のプレースホルダーの判定に使う
            variant_label: string;       // 例: ブラック / M。規格を持たない明細は「規格なし」
            product_image_url: string | null;
            unit_price: number;
            quantity: number;
            subtotal: number;
        }[];
        subtotal: number;
        shipping_fee: number;
        cod_fee: number;
        total: number;
        shipping: {
            recipient_name: string;
            postal_code: string;
            prefecture_name: string;
            city: string;
            address_line1: string;
            address_line2: string | null;
            tel: string;
        };
        histories: { id: number; to_status_label: string; changed_at: string }[];
        is_cancelable: boolean;
    };
};
```

日時はISO 8601で渡し、表示の整形はフロント側（`resources/js/front/lib/date.ts`）で行う。

### 入力値バリデーションルール

キャンセルは送信項目を持たないため FormRequest はない。実行可否はステータスで判定する（下記「キャンセルの可否」）。

### 主要な処理フロー

**注文履歴**

1. ログイン中の会員の注文を `ordered_at` 降順 → `id` 降順で1ページ10件に区切る。
2. `items_summary` は先頭明細の商品名とし、明細が2件以上なら「 ほかN点」（N = 明細数 − 1）を添える。
3. `is_cancelable` は各注文のステータスから判定する。

**注文詳細**

1. `OrderPolicy::view` で自分の注文であることを確認する（他人の注文は403）。
2. `orders` / `order_items` のスナップショット列だけで組み立てる（`products` / `users` を参照しない）。
3. 変更履歴は古い順に並べ、遷移後のステータスと日時のみを出す（操作した管理者名は渡さない）。

**キャンセル（`CancelOrderService`。全体を1トランザクションで囲む）**

1. `OrderPolicy::cancel` で自分の注文であることを確認する（他人の注文は403）。
2. ステータスがキャンセル可能かを再判定する。不可なら何もせず元の画面へ戻し `この注文はキャンセルできません` を表示する。
3. `orders.status` をキャンセルへ更新し、`cancelled_at` に現在日時を記録する。
4. `order_status_histories` に1行追加する（`admin_id = null`）。
5. `RestoreStockFromOrder` で在庫を戻す（規則は [docs/admin/order-show.md](../admin/order-show.md) が正本）。
6. 注文詳細へリダイレクトし `注文をキャンセルしました` を表示する。

### キャンセルの可否

`OrderStatus::isCancelableByCustomer()` が単一情報源で、画面の表示（`is_cancelable`）とサーバー側の実行判定の双方がこれを参照する。

| 現在のステータス | 会員によるキャンセル |
|---|---|
| 注文受付 `received` | 可 |
| 入金待ち `awaiting_payment` | 可 |
| 入金確認済み `payment_confirmed` | 不可 |
| 出荷準備中 `preparing` | 不可 |
| 出荷済み `shipped` | 不可 |
| キャンセル `cancelled` | 不可 |

## 業務ルール

- 入金確認済み以降のキャンセルは返金・出荷手配の判断を伴うため、管理画面からの操作に限定する。
- 会員によるキャンセルは管理者の承認を挟まず即時に確定する（「キャンセル依頼中」のステータスは設けない）。

## 関連ドキュメント

- [docs/order-snapshot.md](../order-snapshot.md) — 表示するスナップショット列の値の出所の正本
- [docs/admin/order-show.md](../admin/order-show.md) — ステータス遷移規則・キャンセル時の在庫戻しの正本
- [docs/front/order-complete.md](order-complete.md) — 注文完了画面（注文履歴への導線）
- [docs/front/mypage-address.md](mypage-address.md) — マイページの配送先住所タブ
- [docs/front/mypage-profile.md](mypage-profile.md) — マイページの会員情報変更・パスワード変更タブ
- [docs/front/favorite.md](favorite.md) — マイページのお気に入りタブ
- [docs/front/common-layout.md](common-layout.md) — ヘッダー・フッターのマイページ導線と、商品画像プレースホルダー（`ProductVisual`）の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/MyPage/OrderHistoryController.php` |
| Controller | `app/Http/Controllers/Front/MyPage/OrderCancelController.php` |
| Service | `app/Services/Front/Order/CancelOrderService.php` |
| Policy | `app/Policies/OrderPolicy.php` |
| Enum | `app/Enums/OrderStatus.php` |
| Layout | `resources/js/front/Layouts/MyPageLayout.tsx` |
| Component | `resources/js/front/Components/MyPage/TabNav.tsx` |
| Component | `resources/js/front/Components/MyPage/OrderCard.tsx` |
| Component | `resources/js/front/Components/MyPage/CancelOrderButton.tsx` |
| Page | `resources/js/front/Pages/MyPage/Orders.tsx` |
| Page | `resources/js/front/Pages/MyPage/OrderShow.tsx` |
| Util | `resources/js/front/lib/date.ts` |
| Test | `tests/Feature/Front/MyPage/OrderHistoryTest.php` |
| Test | `tests/Feature/Front/MyPage/OrderShowTest.php` |
| Test | `tests/Feature/Front/MyPage/OrderCancelTest.php` |

## 受け入れ条件

- 未ログインでログイン画面へリダイレクトされる: `tests/Feature/Front/MyPage/OrderHistoryTest.php`・`OrderShowTest.php`・`OrderCancelTest.php`（各`未ログインではログイン画面へリダイレクトされる`）
- 自分の注文だけが履歴に出る: `tests/Feature/Front/MyPage/OrderHistoryTest.php`（`自分の注文だけが表示される`）
- 注文日の新しい順に並ぶ: 同上（`注文日の新しい順に並ぶ`）
- `items_summary` が明細数に応じて組み立てられる: 同上（`明細が一点なら商品名だけが要約に出る`・`明細が複数なら先頭の商品名に残りの点数が添えられる`）
- キャンセル可否フラグがステータスに従う: 同上（`注文受付と入金待ちの注文はキャンセルできる状態で返る`・`入金確認済み以降の注文はキャンセルできない状態で返る`）
- 1ページ10件で区切られる: 同上（`一ページあたり十件までで区切られる`）
- 自分の注文詳細が表示され、他人の注文は403: `tests/Feature/Front/MyPage/OrderShowTest.php`（`自分の注文の詳細が表示される`・`他人の注文は閲覧できない`）
- 明細がスナップショットで描画され、商品を削除しても残る: 同上（`明細はスナップショットで描画され商品を削除しても残る`・`規格を持たない明細は規格なしと表示される`）
- 銀行振込の注文にのみ振込案内文が出る: 同上（`銀行振込の注文には振込案内文が表示される`・`代引きの注文には振込案内文が表示されない`）
- キャンセル可能なステータスの注文をキャンセルできる: `tests/Feature/Front/MyPage/OrderCancelTest.php`（`注文受付の注文をキャンセルできる`・`入金待ちの注文をキャンセルできる`）
- キャンセル不可のステータスでは実行されない: 同上（`入金確認済みの注文はキャンセルできない`・`出荷済みの注文はキャンセルできない`・`キャンセル済みの注文は再度キャンセルできない`）
- 他人の注文をキャンセルできない: 同上（`他人の注文はキャンセルできない`）
- キャンセルで在庫が戻り、履歴が管理者なしで記録される: 同上（`キャンセルすると在庫が戻る`・`会員によるキャンセルの履歴は管理者を持たずに記録される`）
- タブの現在地表示・レスポンシブのタブ配置・確認モーダルの表示: 自動テストなし。目視確認で担保する
