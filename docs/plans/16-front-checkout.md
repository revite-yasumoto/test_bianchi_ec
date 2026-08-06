# 単位16: フロント - 購入手続き〜注文確認〜注文完了

依存: 単位15, 単位10

**ユーザーが最重視する「注文時スナップショット」を実際に書き込む単位。** 注文確定時に会員情報・商品情報・金額・算出根拠のすべてを `orders` / `order_items` に複製する。

## スコープ

- 購入手続き画面: 配送先選択（登録済み／新規追加モーダル）・支払い方法選択・送料の確定・配達予定日の算出・合計金額の再計算・「カートに戻る」導線
- 注文確認画面: 配送先・支払い方法・商品明細・送料・配達予定日・最終合計金額の最終表示
- 注文完了画面: 注文番号・配達予定日・銀行振込の案内文
- 注文確定処理: スナップショットの書き込み・在庫の減算・カートのクリア・注文番号の採番
- 3ステップの進捗インジケータ（カート → 購入手続き → 注文確認）

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Service / Action） | `laravel-set:laravel` |
| トランザクション・行ロック・在庫の減算 | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php`）

すべて `auth` ミドルウェアを適用する。

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/checkout` | `checkout.index` | 購入手続き |
| POST | `/checkout` | `checkout.store` | 購入手続きの内容をセッションに保存して注文確認へ |
| GET | `/checkout/confirm` | `checkout.confirm` | 注文確認 |
| POST | `/orders` | `orders.store` | 注文確定 |
| GET | `/orders/{order}/complete` | `orders.complete` | 注文完了 |
| POST | `/addresses` | `addresses.store` | 配送先の新規追加（購入手続き画面のモーダルから。単位17でも使う） |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/CheckoutController.php` | `index()` / `store()` / `confirm()` |
| Controller | `app/Http/Controllers/Front/OrderController.php` | `store()` / `complete()`（単位17で `index()` / `show()` を追加） |
| Controller | `app/Http/Controllers/Front/UserAddressController.php` | `store()`（単位17で `update()` / `destroy()` を追加） |
| FormRequest | `app/Http/Requests/Front/Checkout/StoreCheckoutRequest.php` | 配送先ID・支払い方法の検証 |
| FormRequest | `app/Http/Requests/Front/Address/StoreUserAddressRequest.php` | 配送先住所のバリデーション |
| Service | `app/Services/Front/Checkout/CheckoutService.php` | 購入手続きの Props 組み立て・金額の再計算 |
| Service | `app/Services/Front/Order/PlaceOrderService.php` | **注文確定処理の中核**。スナップショット書き込み・在庫減算・カートクリアをトランザクションで実行 |
| Action | `app/Actions/Front/Order/GenerateOrderNumber.php` | 注文番号（`BNC-YYMM-NNNN`）の採番 |
| Action | `app/Actions/Front/Order/BuildOrderSnapshot.php` | 会員・配送先・金額・算出根拠のスナップショット値を組み立て |
| Action | `app/Actions/Front/Order/BuildOrderItemSnapshots.php` | 商品スナップショット値を組み立て |
| Policy | `app/Policies/OrderPolicy.php` | 自分の注文のみ閲覧できることを保証 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/front/Pages/Checkout/Index.tsx` | ステップインジケータ＋左に配送先・支払い方法、右にご注文内容サイドカード |
| Page | `resources/js/front/Pages/Checkout/Confirm.tsx` | 配送先・支払い方法・商品明細のカード＋右に金額サイドカード |
| Page | `resources/js/front/Pages/Order/Complete.tsx` | チェックマーク・完了メッセージ・注文番号・配達予定日・銀行振込案内文・「注文履歴を見る」「TOPへ戻る」 |
| Component | `resources/js/front/Components/Checkout/StepIndicator.tsx` | 3ステップの進捗インジケータ |
| Component | `resources/js/front/Components/Checkout/AddressSelector.tsx` | ラジオ形式の配送先カード＋「＋ 新しいお届け先を追加」 |
| Component | `resources/js/front/Components/Checkout/AddressModal.tsx` | 配送先追加モーダル。都道府県を選ぶと送料・お届け日数を即時に表示 |
| Component | `resources/js/front/Components/Checkout/PaymentSelector.tsx` | ラジオ形式の支払い方法カード（銀行振込／代金引換） |
| Component | `resources/js/front/Components/Checkout/OrderSummaryCard.tsx` | 商品明細・商品合計・送料・手数料・合計・お届け予定日 |

## インターフェース ＆ データロジック

### Props の型

```ts
// Checkout/Index.tsx
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
  items: CartRow[];                  // 単位15の型を再利用
  addresses: AddressData[];
  prefectures: { id: number; name: string }[];
  shippingByPrefecture: Record<number, { fee: number; delivery_days: number }>;  // 都道府県ID → 送料・日数
  selected: { address_id: number | null; payment_method: 'bank_transfer' | 'cod' };
  amounts: {
    subtotal: number;
    shipping_fee: number;
    cod_fee: number;
    total: number;
    estimated_delivery_date: string;   // '8月8日（土）'
  };
  ecSetting: { free_shipping_threshold: number; cod_fee: number };
};
```

配送先を切り替えたときの送料・お届け予定日・合計金額の再計算は、`shippingByPrefecture` を使って**フロント側で即時に**行う（要件の「リアルタイムで再計算」）。ただし注文確定時はサーバー側で必ず再計算し、その値を保存する。

### バリデーション

`StoreCheckoutRequest`:

| 項目 | ルール |
|---|---|
| `address_id` | 必須・`user_addresses.id` に存在・**ログイン中の会員が所有する住所であること** |
| `payment_method` | 必須・`App\Enums\PaymentMethod` の値のいずれか |

`StoreUserAddressRequest`:

| 項目 | ルール |
|---|---|
| `label` | 必須・文字列・最大100 |
| `recipient_name` | 必須・文字列・最大100 |
| `postal_code` | 必須・`\d{3}-?\d{4}` 形式・最大8 |
| `prefecture_id` | 必須・`prefectures.id` に存在 |
| `city` | 必須・文字列・最大100 |
| `address_line1` | 必須・文字列・最大255 |
| `address_line2` | 任意・文字列・最大255 |
| `tel` | 必須・`[\d-]{10,20}` 形式 |
| `is_default` | 任意・boolean |

### 注文確定処理（`PlaceOrderService`）

**全体を1トランザクションで囲む。** 順序が重要。

1. **カートの再取得と検証**
   - カートが空なら「カートに商品がありません」でカートページへ戻す
   - `stocks` の行を `lockForUpdate()` で取得する（同時注文による在庫のマイナスを防ぐ）
   - 各行について、商品が公開中・バリエーションが取扱対象・在庫が数量以上であることを再検証する
   - 1件でも満たさなければ全体を中断し、「在庫が不足している商品があります」でカートページへ戻す
2. **配送先の再検証**: `address_id` がログイン中の会員の住所であることを再確認する
3. **金額の再計算**: `ShippingCalculator`（単位10）で送料・代引き手数料・配達予定日・合計を算出する。**セッションに保存した金額をそのまま使わず、必ず再計算する**
4. **注文番号の採番**: `BNC-{YY}{MM}-{NNNN}`。`NNNN` は当月内の連番（`orders` の当月分の件数 + 1 を4桁ゼロ埋め）。一意制約違反が起きた場合は最大3回まで再採番する
5. **`orders` の作成**（スナップショット）

   | 列 | 値の出所 |
   |---|---|
   | `order_number` | 採番結果 |
   | `user_id` | ログイン中の会員ID |
   | `status` | 支払い方法が銀行振込なら `awaiting_payment`（入金待ち）、代引きなら `received`（注文受付） |
   | `payment_method` | 選択値 |
   | `ordered_at` | 現在日時 |
   | `member_code_snapshot` | `users.member_code` |
   | `customer_name` / `customer_name_kana` / `customer_email` / `customer_tel` | `users` の各値 |
   | `shipping_recipient_name` / `shipping_postal_code` / `shipping_city` / `shipping_address_line1` / `shipping_address_line2` / `shipping_tel` | `user_addresses` の各値 |
   | `shipping_prefecture_name` | `prefectures.name`（**IDではなく名称**） |
   | `subtotal` / `shipping_fee` / `cod_fee` / `total` | 再計算結果 |
   | `free_shipping_threshold` | `ec_settings.free_shipping_threshold` |
   | `shipping_fee_base` | `shipping_settings.fee`（無料適用前の素の送料） |
   | `delivery_days` | `shipping_settings.delivery_days` |
   | `estimated_delivery_date` | `ordered_at` + `delivery_days` 日 |
   | `bank_transfer_note` | 銀行振込のとき `ec_settings.bank_transfer_note`、代引きのとき `null` |

6. **`order_items` の作成**（商品スナップショット）

   | 列 | 値の出所 |
   |---|---|
   | `product_id` / `product_variant_id` | 参照用 |
   | `product_code` | `products.product_code` |
   | `product_name` | `products.name` |
   | `category_name` | `categories.name` |
   | `sku_code` / `size_name` / `color_name` | `product_variants` の各値 |
   | `product_image_path` | メイン画像（`sort_order = 0`）の `path`。なければ `null` |
   | `unit_price` | `products.price`（**注文時点の価格**） |
   | `quantity` | `cart_items.quantity` |
   | `subtotal` | `unit_price × quantity` |

7. **在庫の減算**: `stocks.quantity` から各明細の `quantity` を減算する
8. **`order_status_histories` の初期行を作成**: `from_status = null`、`to_status` = 初期ステータス、`admin_id = null`、`changed_at` = 現在日時
9. **カートのクリア**: そのユーザーの `cart_items` を全削除する
10. **セッションの購入手続き情報をクリア**する
11. 注文完了画面（`orders.complete`）へリダイレクトする

### 注文完了画面へのアクセス制御

- `OrderPolicy` で自分の注文であることを確認する（他人の注文番号を直接叩けないようにする）
- リロードしても表示できる（注文はDBに確定済みのため、二重注文にはならない）
- 二重送信の防止: 注文確定は POST で行い、成功後は注文完了画面へリダイレクトする（POST-Redirect-GET）

### 画面間の状態の持ち回り

購入手続き（配送先ID・支払い方法）→ 注文確認 の間はセッションに保存する。

- 注文確認画面をセッションなしで直接開いた場合は購入手続き画面へリダイレクトする
- 金額はセッションの値を表示に使わず、注文確認画面でも都度サーバー側で再計算する

## 業務ルール

- オンライン決済は実装しない。支払い方法は銀行振込と代引きのみ
- 銀行振込の注文は初期ステータス「入金待ち」、代引きの注文は「注文受付」
- 配達予定日は暦日で加算する（`ShippingCalculator` の規定に従う）
- 配送先を切り替えると送料・お届け予定日・合計金額が即時に再計算される（例: 北海道 1,000円 ／ 大阪府 500円）
- 注文確定後、`orders` / `order_items` に保存した値は一切変更しない。商品価格・商品名・カテゴリ名・会員情報・住所・送料設定・EC基本設定が後から変わっても、その注文の表示・金額は変わらない
- 在庫は注文確定時に減算する。カート投入時には確保しない
- 注文完了メールは送信しない（メール送信は要件のスコープ外）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。本単位のテストは特に重点を置く。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/Checkout/CheckoutIndexTest.php` | 未ログインでログイン画面へリダイレクトされること／カートが空なら購入手続きに入れないこと／登録済み配送先が一覧されること／都道府県ごとの送料表が渡されること |
| `tests/Feature/Front/Checkout/CheckoutAmountTest.php` | 配送先の都道府県で送料が変わること（北海道1,000円／大阪府500円）／しきい値以上で送料0になること／代引き選択で手数料が加算されること／銀行振込で手数料が0になること／合計金額の算出 |
| `tests/Feature/Front/Checkout/CheckoutConfirmTest.php` | セッションなしで注文確認を開くと購入手続きへリダイレクトされること／他人の配送先IDを指定すると拒否されること |
| `tests/Feature/Front/Order/PlaceOrderTest.php` | 注文が作成されること／注文番号の形式（`BNC-YYMM-NNNN`）／月内連番／銀行振込は「入金待ち」・代引きは「注文受付」で作成されること／カートが空になること／`order_status_histories` の初期行が作られること |
| `tests/Feature/Front/Order/OrderSnapshotTest.php` | **本単位の中核テスト。** 注文確定後に以下を変更しても `orders` / `order_items` の値が変わらないこと: 商品価格／商品名／カテゴリ名／会員氏名・メール・電話番号／配送先住所／`shipping_settings.fee` と `delivery_days`／`ec_settings` の3項目。さらに商品を削除しても明細が残り `product_id` が `null` になること |
| `tests/Feature/Front/Order/OrderStockTest.php` | 在庫が明細の数量分減算されること／在庫不足のとき注文が作成されず在庫も減らないこと／トランザクションが全件ロールバックされること |
| `tests/Feature/Front/Order/OrderAmountRecalculationTest.php` | セッションに改ざんされた金額を入れても、保存される金額はサーバー側の再計算結果になること |
| `tests/Feature/Front/Order/OrderCompleteTest.php` | 自分の注文完了画面が表示されること／他人の注文完了画面が403になること／銀行振込の注文に振込案内文が表示されること／代引きの注文に表示されないこと |
| `tests/Feature/Front/Address/StoreUserAddressTest.php` | 配送先が追加されること／`is_default` を立てると他の住所の既定が解除されること／必須項目・形式のバリデーション |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **初期ステータス**: 要件は銀行振込を「入金待ち」と明記。代引きは明記がないため推奨=**「注文受付」**。
2. **注文番号の形式**: 推奨=`BNC-YYMM-NNNN`（月内連番の4桁ゼロ埋め）。モックの `VLC-` はブランド統一の決定に従い `BNC-` とする。
3. **同時注文時の在庫競合**: 要件に記載なし。推奨=**`lockForUpdate()` で在庫行をロックし、不足していれば注文を作成しない**。在庫のマイナスを許さない。
4. **画面間の状態の持ち回り**: セッション／隠しフォームのいずれか。推奨=**セッション**（URLに配送先IDや金額を出さない）。
5. **金額の再検証**: 推奨=**注文確認画面と注文確定処理の双方でサーバー側から再計算する**（クライアントやセッションの金額を信用しない）。
6. **注文完了メール**: 送信しない（要件のスコープ外）。銀行振込の案内文は注文完了画面と注文詳細に表示する。
7. **注文確定後のカートの扱い**: 推奨=**全削除する**。
8. **注文完了画面のリロード**: 注文IDをURLに含めるため、リロードしても二重注文にならない。
9. **「新しいお届け先を追加」の保存範囲**: モックはモーダルで住所を入力する。推奨=**`user_addresses` に保存する**（一時利用ではなく登録扱いにし、マイページの配送先住所にも現れる）。
10. **在庫切れ商品がカートにある状態での購入手続き**: 推奨=購入手続き画面に入れず、カートページで「在庫が不足している商品があります」を表示する。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/front/checkout.md`（購入手続き・注文確認）
  - `docs/front/order-complete.md`（注文完了）
  - `docs/order-snapshot.md`（**注文時スナップショットの設計方針と全列の値の出所の正本**。横断ドキュメント。単位01・07・17の仕様書からリンクする）
- 本計画ファイルを削除し、トラッカーの状態を更新する
