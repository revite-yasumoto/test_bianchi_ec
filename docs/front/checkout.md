# フロント 購入手続き・注文確認

本書は**注文確定処理**（在庫の再検証・スナップショットの書き込み・在庫の減算・カートのクリア・注文番号の採番）と**購入手続きの状態の持ち回り**の正本。保存する値の出所は [docs/order-snapshot.md](../order-snapshot.md)、金額の算出規則は [docs/shipping-calculation.md](../shipping-calculation.md) が正本であり、本書では再掲しない。

## 機能概要

- **対象画面・機能の目的:** カートの内容に配送先と支払い方法を決め、確定金額と配達予定日を提示したうえで注文を確定する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/checkout` | `checkout.index` | 購入手続き |
| POST | `/checkout` | `checkout.store` | 選択内容をセッションに保存して注文確認へ |
| GET | `/checkout/confirm` | `checkout.confirm` | 注文確認 |
| POST | `/orders` | `orders.store` | 注文確定 |
| POST | `/addresses` | `addresses.store` | 配送先の追加（購入手続きのモーダルから。マイページからも使う） |

- **アクセス権限・ミドルウェア:** 全ルートを `auth`（`web` ガード）で保護する。未ログインのアクセスはログイン画面へ遷移する。配送先は自分の `user_addresses` に限定して検証する（他人の住所IDを送っても通らない）。
- **本ドキュメントのスコープ:** 購入手続き・注文確認の2画面と、配送先の追加・注文確定処理。注文完了画面は [docs/front/order-complete.md](order-complete.md)、カートは [docs/front/cart.md](cart.md) が正本。配送先の一覧・編集・削除は単位17が扱う。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `cart_items` | 注文する明細。確定後に全削除する |
| `product_variants` / `products` / `categories` / `product_images` | 明細の表示とスナップショットの値 |
| `stocks` | 購入可否の再検証と、確定時の減算 |
| `user_addresses` | 配送先の選択・追加 |
| `prefectures` / `shipping_settings` | 送料・お届け日数・都道府県名 |
| `ec_settings` | 送料無料しきい値・代引き手数料・銀行振込の案内文 |
| `users` | 会員スナップショット |
| `orders` / `order_items` | 注文と明細（書き込み） |
| `order_status_histories` | 初期ステータスの履歴（書き込み） |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

購入手続き（PCは2カラム、SP幅では1カラムに縦積み）:

```
+--------------------------------------------------------------+
| (1) カート — (2) 購入手続き — (3) 注文確認                     |
| 購入手続き（h1）                                               |
| +------------------------------+ +-------------------------+ |
| | お届け先                      | | ご注文内容               | |
| | (o) 架空 太郎（自宅）［既定］  | | 商品名 ×2       ¥29,600 | |
| |     〒150-0041 東京都…        | | 商品名 ×1        ¥3,200 | |
| | ( ) 架空 花子（勤務先）       | | ------------------------| |
| |     〒060-0001 北海道…        | | 商品合計        ¥32,800 | |
| | [ ＋ 新しいお届け先を追加 ]    | | 送料（東京都）     ¥500 | |
| |                              | | 代引き手数料         —  | |
| | お支払い方法                  | | 合計            ¥33,300 | |
| | (o) 銀行振込（前払い）         | | +---------------------+ | |
| | ( ) 代金引換                  | | | お届け予定日        | | |
| |                              | | | 8月16日（土）       | | |
| |                              | | +---------------------+ | |
| |                              | | [ 注文内容を確認する ]   | |
| |                              | | [   カートに戻る   ]    | |
| +------------------------------+ +-------------------------+ |
+--------------------------------------------------------------+
```

- 進捗インジケータは3ステップ（カート → 購入手続き → 注文確認）で、現在ステップまでをブランド色で塗る。購入手続きは2、注文確認は3。
- 配送先・支払い方法はラジオボタンで、選択中のカードを枠線と背景で示す。配送先は既定の住所を先頭に並べ、既定の住所には「既定」バッジを付ける。
- **配送先・支払い方法を切り替えると、送料・代引き手数料・合計・お届け予定日がその場で再計算される。** 配送先が1件も無い場合は「注文内容を確認する」を非活性にする。
- 「＋ 新しいお届け先を追加」でモーダルを開く。都道府県を選ぶとその場で「送料 ¥500 ／ お届けまで3日」を表示する。保存すると追加した住所が選択済みの状態で購入手続きに戻る。

注文確認:

```
+--------------------------------------------------------------+
| (1) カート — (2) 購入手続き — (3) 注文確認                     |
| 注文内容の確認（h1）                                           |
| +------------------------------+ +-------------------------+ |
| | お届け先                      | | お支払い金額             | |
| | 架空 太郎（自宅）              | | 商品合計        ¥32,800 | |
| | 〒150-0041 東京都…            | | 送料（東京都）     ¥500 | |
| |------------------------------| | 代引き手数料         —  | |
| | お支払い方法                  | | 合計            ¥33,300 | |
| | 銀行振込（前払い）             | | +---------------------+ | |
| | （振込案内文）                | | | お届け予定日        | | |
| |------------------------------| | | 8月16日（土）       | | |
| | 商品明細                      | | +---------------------+ | |
| | [画] 商品名                   | | [  注文を確定する  ]    | |
| |      レッド / M / 数量 2      | | [ 購入手続きに戻る ]    | |
| +------------------------------+ +-------------------------+ |
+--------------------------------------------------------------+
```

- 代引き手数料は 0 円のとき金額に代えて「—」を表示する。送料は 0 円のとき「無料」と表示する。
- 支払い方法の説明文は、銀行振込のとき `ec_settings.bank_transfer_note`、代引きのとき「商品到着時に配達員へお支払いください。」を表示する。

## インターフェース ＆ データロジック

- **購入手続き（`front/Checkout/Index`）のProps:**

```ts
type AddressData = {
    id: number;
    label: string;
    recipient_name: string;
    postal_code: string;
    prefecture_id: number;
    prefecture_name: string;
    city: string;
    address_line1: string;
    address_line2: string | null;
    tel: string;
    is_default: boolean;
};

type Props = {
    items: CartRow[];                  // 型は docs/front/cart.md が正本
    addresses: AddressData[];          // 既定を先頭にID昇順
    prefectures: { id: number; name: string }[];
    shippingByPrefecture: Record<number, { fee: number; delivery_days: number }>;
    selected: { address_id: number | null; payment_method: PaymentMethod };
    ecSetting: { free_shipping_threshold: number; cod_fee: number };
    deliveryBaseDate: string;          // 配達予定日の算出基準日（サーバーの当日。ISO 8601）
};
```

- **注文確認（`front/Checkout/Confirm`）のProps:**

```ts
type Props = {
    items: CartRow[];
    address: AddressData;
    paymentMethod: PaymentMethod;
    amounts: {
        subtotal: number;
        shipping_fee: number;
        cod_fee: number;
        total: number;
        estimated_delivery_date: string;   // ISO 8601 の日付
    };
    bankTransferNote: string | null;       // 銀行振込のときのみ
};
```

- **金額の算出責任:** 購入手続き画面の金額は、`shippingByPrefecture` と `ecSetting` を使って**フロント側で即時に**算出する（配送先・支払い方法の切り替えに追従させるため）。この結果は表示専用であり、注文確認画面と注文確定処理では必ずサーバー側で再計算した値を使う。フロント側の算出規則は [docs/shipping-calculation.md](../shipping-calculation.md) と同一で、`calculateCheckoutAmounts` が持つ。

- **入力値バリデーションルール（`StoreCheckoutRequest`）:**

| 項目 | ルール |
|---|---|
| `address_id` | 必須・整数・`user_addresses.id` に存在し、かつログイン中の会員が所有すること |
| `payment_method` | 必須・`App\Enums\PaymentMethod` の値のいずれか |

- **入力値バリデーションルール（`StoreUserAddressRequest`）:**

| 項目 | ルール |
|---|---|
| `label` | 必須・文字列・最大100 |
| `recipient_name` | 必須・文字列・最大100 |
| `postal_code` | 必須・文字列・最大8・`\d{3}-?\d{4}` 形式 |
| `prefecture_id` | 必須・整数・`prefectures.id` に存在 |
| `city` | 必須・文字列・最大100 |
| `address_line1` | 必須・文字列・最大255 |
| `address_line2` | 任意・文字列・最大255 |
| `tel` | 必須・文字列・`[\d-]{10,20}` 形式 |
| `is_default` | 任意・boolean |
| `use_for_checkout` | 任意・boolean。真のとき追加した住所を購入手続きの選択済み配送先にする |

- **画面間の状態の持ち回り:** 配送先IDと支払い方法をセッション（`checkout.address_id` / `checkout.payment_method`）で持ち回る。URLに配送先IDや金額を出さない。金額はセッションに持たせない。

- **主要な処理フロー:**

**購入手続きの表示**
1. カート明細を取得する（[docs/front/cart.md](cart.md) と同じ行の形）。
2. 明細が0件、または購入できない行が1件でもあれば、カートページへ戻して理由（`カートに商品がありません。` / `在庫が不足している商品があります。数量を変更するか、その商品を削除してください。`）を表示する。
3. 自分の配送先を既定を先頭にして一覧し、都道府県の一覧と都道府県ごとの送料・お届け日数を渡す。
4. 初期選択は、セッションに選択済みの配送先があればそれ、無ければ一覧の先頭（＝既定の住所）。支払い方法はセッションの値、無ければ銀行振込。

**配送先の追加**
1. 入力を検証し、自分の `user_addresses` に1件追加する。
2. 既定に指定した場合は、同じ会員の他の住所の既定を解除する（既定は常に1件以下）。
3. `use_for_checkout` が真なら、追加した住所をセッションの選択済み配送先にする。
4. 元の画面へ戻して `お届け先を追加しました` を表示する。

**注文内容を確認する**
1. 配送先ID・支払い方法を検証してセッションに保存し、注文確認へ遷移する。
2. 注文確認では、カートの購入可否を再確認し（満たさなければカートページへ）、セッションの配送先が自分の住所として存在することを再確認する（存在しなければ購入手続きへ）。
3. 金額はサーバー側で再計算した値を表示する。

**注文を確定する（`PlaceOrderService`。全体を1トランザクションで囲む）**
1. カート明細を取得する。空なら中断してカートページへ戻す。
2. 明細のバリエーションに対応する `stocks` を `lockForUpdate()` でロックする（同時注文による在庫のマイナスを防ぐため、検証より前にロックする）。
3. 各明細について、商品が公開中・バリエーションが取扱対象・在庫が数量以上であることを再検証する。1件でも満たさなければ全体を中断してカートページへ戻す。
4. セッションの配送先が自分の住所として存在することを再確認する。無ければ中断して購入手続きへ戻す。
5. 商品合計を明細の現在価格から算出し、送料・代引き手数料・配達予定日・合計を再計算する。
6. 注文番号を採番する。
7. `orders` を1件作成する（値の出所は [docs/order-snapshot.md](../order-snapshot.md)）。
8. `order_items` を明細分作成する（同上）。
9. 各明細の数量だけ `stocks.quantity` を減算する。
10. `order_status_histories` に初期行を作成する（`from_status = null`、`to_status` = 初期ステータス、`admin_id = null`）。
11. その会員の `cart_items` を全削除する。
12. 購入手続きのセッションを破棄し、注文完了画面へリダイレクトする（POST-Redirect-GET）。

中断した場合はトランザクション全体がロールバックされ、注文・明細・履歴は作成されず、在庫もカートも元のまま残る。

**注文番号の採番（`GenerateOrderNumber`）**
- 形式は `BNC-{YY}{MM}-{NNNN}`。`NNNN` は `ordered_at` が当月内の `orders` の件数 + 1 を4桁ゼロ埋めしたもの。
- キャンセル済みの注文も件数に含めるため連番に欠番が出うるが、一意性と月内の通し番号という性質は保たれる。
- 採番した番号が既に存在する場合は、空いている番号まで最大3回ずらす。3回で空きが見つからなければ注文を中断する。

## 業務ルール

- オンライン決済は実装しない。支払い方法は銀行振込と代引きのみで、クレジットカード等の選択肢を画面に出さない。
- 在庫は注文確定時にのみ減算する。カート投入時にも購入手続きに入った時点でも確保しない。
- 在庫が不足している商品がカートにある間は購入手続き画面に入れない。カートページで数量を変更するか、その商品を削除してもらう。
- 購入手続きのモーダルから追加した住所は一時利用ではなく `user_addresses` に登録する（マイページの配送先住所にも現れる）。
- 注文完了メールは送信しない（メール送信は要件のスコープ外）。銀行振込の案内は注文完了画面と注文詳細に表示する。
- 会員登録・ログインを経ないゲスト購入は行わない。

## 関連ドキュメント

- [docs/order-snapshot.md](../order-snapshot.md) — 保存するスナップショットの値の出所の正本
- [docs/shipping-calculation.md](../shipping-calculation.md) — 送料・代引き手数料・配達予定日・合計の算出規則の正本
- [docs/front/cart.md](cart.md) — カート明細・価格方針・購入不可行の扱いの正本
- [docs/front/order-complete.md](order-complete.md) — 注文完了画面
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout`・トースト・共有プロパティの正本
- [docs/admin/order-show.md](../admin/order-show.md) — ステータス遷移規則とキャンセル時の在庫戻しの正本
- [docs/admin/ec-setting.md](../admin/ec-setting.md) — 送料無料しきい値・代引き手数料・振込案内文の設定元
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/CheckoutController.php` |
| Controller | `app/Http/Controllers/Front/OrderController.php` |
| Controller | `app/Http/Controllers/Front/UserAddressController.php` |
| FormRequest | `app/Http/Requests/Front/Checkout/StoreCheckoutRequest.php` |
| FormRequest | `app/Http/Requests/Front/Address/StoreUserAddressRequest.php` |
| Service | `app/Services/Front/Checkout/CheckoutService.php` |
| Service | `app/Services/Front/Order/PlaceOrderService.php` |
| Action | `app/Actions/Front/Order/GenerateOrderNumber.php` |
| Action | `app/Actions/Front/Order/BuildOrderSnapshot.php` |
| Action | `app/Actions/Front/Order/BuildOrderItemSnapshots.php` |
| Action | `app/Actions/Front/Address/StoreUserAddress.php` |
| Exception | `app/Exceptions/OrderNotPlaceableException.php` |
| Enum | `app/Enums/PaymentMethod.php` |
| Page | `resources/js/front/Pages/Checkout/Index.tsx` |
| Page | `resources/js/front/Pages/Checkout/Confirm.tsx` |
| Component | `resources/js/front/Components/Checkout/StepIndicator.tsx` |
| Component | `resources/js/front/Components/Checkout/AddressSelector.tsx` |
| Component | `resources/js/front/Components/Checkout/AddressModal.tsx` |
| Component | `resources/js/front/Components/Checkout/PaymentSelector.tsx` |
| Component | `resources/js/front/Components/Checkout/OrderSummaryCard.tsx` |
| Util | `resources/js/front/lib/checkoutAmounts.ts` |
| Util | `resources/js/front/lib/delivery.ts` |
| Test | `tests/Concerns/CreatesCheckoutScenario.php` |
| Test | `tests/Feature/Front/Checkout/CheckoutIndexTest.php` |
| Test | `tests/Feature/Front/Checkout/CheckoutAmountTest.php` |
| Test | `tests/Feature/Front/Checkout/CheckoutConfirmTest.php` |
| Test | `tests/Feature/Front/Order/PlaceOrderTest.php` |
| Test | `tests/Feature/Front/Order/OrderSnapshotTest.php` |
| Test | `tests/Feature/Front/Order/OrderStockTest.php` |
| Test | `tests/Feature/Front/Order/OrderAmountRecalculationTest.php` |
| Test | `tests/Feature/Front/Address/StoreUserAddressTest.php` |

## 受け入れ条件

- 未ログインで購入手続き・注文確認・注文確定にアクセスするとログイン画面へ遷移する: `tests/Feature/Front/Checkout/CheckoutIndexTest.php`・`tests/Feature/Front/Checkout/CheckoutConfirmTest.php`・`tests/Feature/Front/Order/PlaceOrderTest.php`（各 `未ログインではログイン画面へリダイレクトされる`）
- カートが空・在庫不足・在庫切れのときは購入手続きに入れない: `tests/Feature/Front/Checkout/CheckoutIndexTest.php`（`カートが空のときは購入手続きに入れずカートへ戻される` ほか2件）
- 配送先が既定を先頭に一覧され、他人の住所は出ない: 同上（`登録済みの配送先が既定を先頭にして一覧される`・`他人の配送先は一覧されない`）
- 都道府県ごとの送料・お届け日数と基本設定・基準日が渡される: 同上（`都道府県ごとの送料とお届け日数が渡される`）
- セッションの選択内容が初期選択になる: 同上（`セッションで選択済みの配送先と支払い方法が初期選択になる`）
- 都道府県ごとの送料・送料無料の境界・代引き手数料・合計・配達予定日がサーバー側で算出される: `tests/Feature/Front/Checkout/CheckoutAmountTest.php`（正常系・境界値とも）
- セッションなし・他人の住所・カートが空の状態で注文確認を開くとやり直しになる: `tests/Feature/Front/Checkout/CheckoutConfirmTest.php`（`セッションを持たずに開くと購入手続きへ戻される` ほか3件）
- 注文確認に配送先・支払い方法・明細・振込案内文が表示される: 同上（`配送先と支払い方法と商品明細が表示される`・`銀行振込のときは振込案内文が渡される`・`代引きのときは振込案内文が渡されない`）
- 他人の配送先・未定義の支払い方法が拒否され、正しい選択内容はセッションに保持される: 同上（`他人の配送先を送信すると購入手続きの保存が拒否される` ほか2件）
- 注文が作成され、注文番号が `BNC-YYMM-NNNN` の月内連番になる: `tests/Feature/Front/Order/PlaceOrderTest.php`（`注文番号は接頭辞と年月と月内連番で採番される`・`月内の連番は既存の注文の件数に続く`・`前月までの注文は月内連番に含まれない`）
- 銀行振込は入金待ち・代引きは注文受付で作成される: 同上（`銀行振込の注文は入金待ちで作成される`・`代引きの注文は注文受付で作成され振込案内文を持たない`）
- 確定後にカートが空になり、他の会員のカートは残る: 同上（`注文確定後にカートが空になる`・`他の会員のカートは削除されない`）
- ステータス履歴の初期行が作成され、購入手続きのセッションが消える: 同上（`ステータス履歴の初期行が作成される`・`注文確定後は購入手続きのセッションが消える`）
- 会員・配送先・金額の算出根拠・商品情報がスナップショットされる: `tests/Feature/Front/Order/OrderSnapshotTest.php`（前半4件）
- 商品価格・商品名・カテゴリ名・会員情報・配送先住所・送料設定・基本設定を変更しても注文の内容が変わらない: 同上（後半7件）
- 商品を削除しても明細が残り、商品への参照だけが `null` になる: 同上（`商品を削除しても明細は残り商品への参照だけがなくなる`）
- 在庫が明細の数量分だけ減算される: `tests/Feature/Front/Order/OrderStockTest.php`（`在庫が明細の数量分だけ減算される` ほか2件）
- 在庫不足・非公開・取扱対象外のときは注文が作成されず、在庫・カート・履歴が元のまま残る: 同上（`在庫が不足していると注文が作成されず在庫も減らない` ほか3件）
- セッション・リクエストで金額を送っても保存される金額がサーバーの再計算結果になる: `tests/Feature/Front/Order/OrderAmountRecalculationTest.php`（前半2件）
- 注文確認の表示後に価格・送料設定・基本設定が変わっても確定時の値で保存される: 同上（後半3件）
- 配送先の追加・既定の付け替え・購入手続きからの選択・バリデーション: `tests/Feature/Front/Address/StoreUserAddressTest.php`（正常系・異常系とも）
- 進捗インジケータが現在ステップまで塗られる: 自動テストなし。目視確認で担保する
- 配送先・支払い方法を切り替えると送料・代引き手数料・合計・お届け予定日が即座に変わる: 自動テストなし。目視確認で担保する
- 配送先追加モーダルで都道府県を選ぶと送料とお届け日数が表示され、保存後にその住所が選択済みになる: 自動テストなし。目視確認で担保する
- Props型定義の整合性: `npx tsc --noEmit`
- 在庫行のロックによる同時注文の排他: SQLite（テスト用DB）はロック構文を解釈しないため自動テストで担保できない。本番と同じ MySQL の環境での確認が必要
