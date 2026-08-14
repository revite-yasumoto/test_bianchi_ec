# 送料・配達予定日・合計金額の算出

本ドキュメントは、送料・代引き手数料・配達予定日・合計金額の算出ロジックの**正本**である。カート・購入手続き・注文完了・注文詳細など、金額を表示・確定するすべての画面はこの規則に従い、各画面の仕様書は本ドキュメントへリンクして差分のみを記述する。

## 機能概要

- **対象機能の目的:** 配送先の都道府県・商品合計（税込）・支払方法から、適用送料・代引き手数料・配達予定日・合計金額を一意に決定する。
- **URL / メソッド:** なし（画面を持たないサービスクラス。各画面のController・Serviceから呼び出す）
- **アクセス権限・ミドルウェア:** なし（呼び出し元の画面の権限に従う）
- **本ドキュメントのスコープ:** 算出規則と入出力。設定値の編集画面は [docs/admin/shipping-setting.md](admin/shipping-setting.md) と [docs/admin/ec-setting.md](admin/ec-setting.md) が正本。

## 使用テーブル

`shipping_settings`（都道府県ごとの送料・配送予定日数）と `ec_settings`（送料無料しきい値・代引き手数料）を参照する。定義は [docs/2_database.md](2_database.md) が正本。

`ec_settings` は単一行（`id = 1`）で、`EcSettingProvider` がインスタンス内に保持して同一リクエスト中の重複クエリを避ける。アプリケーションキャッシュには載せず、設定変更を次のリクエストから即時反映させる。

## ユーザーインターフェース仕様（ワイヤーフレーム）

なし（画面を持たない）。金額の表示形式は各画面の仕様書に従う。

## インターフェース ＆ データロジック

- **入力:**

| 引数 | 型 | 内容 |
|---|---|---|
| `$prefectureId` | `int` | 配送先の都道府県ID（`prefectures.id`。1〜47） |
| `$itemsTotal` | `int` | 商品合計（税込） |
| `$paymentMethod` | `App\Enums\PaymentMethod` | `BankTransfer` / `Cod` |

- **出力（`App\Services\Shipping\ShippingCalculation`）:**

| プロパティ | 型 | 内容 | `orders` の対応列 |
|---|---|---|---|
| `feeBase` | `int` | 素の送料（該当都道府県の `shipping_settings.fee`） | `shipping_fee_base` |
| `fee` | `int` | 適用送料（送料無料適用後） | `shipping_fee` |
| `codFee` | `int` | 適用代引き手数料 | `cod_fee` |
| `deliveryDays` | `int` | 配送予定日数 | `delivery_days` |
| `estimatedDeliveryDate` | `CarbonImmutable` | 配達予定日 | `estimated_delivery_date` |
| `total` | `int` | 合計金額 | `total` |
| `freeShippingThreshold` | `int` | 判定に使った送料無料しきい値 | `free_shipping_threshold` |

- **入力値バリデーションルール:** なし（呼び出し元の画面がバリデーション済みの値を渡す）。指定した都道府県の `shipping_settings` が存在しない場合は `ModelNotFoundException` を送出する。

- **算出規則:**

| 出力項目 | 算出方法 |
|---|---|
| `feeBase` | 該当都道府県の `shipping_settings.fee` |
| `fee` | 商品合計 >= `ec_settings.free_shipping_threshold` なら `0`、それ未満なら `feeBase` |
| `codFee` | 支払方法が代引きなら `ec_settings.cod_fee`、銀行振込なら `0` |
| `deliveryDays` | 該当都道府県の `shipping_settings.delivery_days` |
| `estimatedDeliveryDate` | 当日日付 + `deliveryDays` 日（暦日で加算） |
| `total` | 商品合計 + `fee` + `codFee` |

## 業務ルール

- 送料無料しきい値の判定は**商品合計（税込）のみ**で行う。送料・代引き手数料は判定に含めない。しきい値と同額のときは無料とする。
- 送料無料が適用された場合、送料は `0` として扱い、画面では「無料」と表記する。
- 配達予定日は暦日で加算する。土日祝・年末年始などの休業日は考慮しない。
- 価格はすべて税込で保持し、税額の内訳は算出・表示しない。
- 設定の変更は以降の新規注文にのみ影響する。確定済みの注文は上表の対応列に注文時点の値をスナップショットとして保持しているため、後から設定を変えても金額は変わらない。
- 海外発送は対象外のため、都道府県は47件固定で国外の配送先を扱わない。

## 関連ドキュメント

- [docs/admin/shipping-setting.md](admin/shipping-setting.md) — 都道府県ごとの送料・配送予定日数を編集する画面
- [docs/admin/ec-setting.md](admin/ec-setting.md) — 送料無料しきい値・代引き手数料・銀行振込の案内文を編集する画面
- [docs/front/checkout.md](front/checkout.md) — 算出結果を確定金額として保存する購入手続き・注文確定の正本
- [docs/order-snapshot.md](order-snapshot.md) — 算出結果を `orders` のどの列へ保存するかの正本
- [docs/2_database.md](2_database.md) — `shipping_settings`・`ec_settings`・`orders` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Service | `app/Services/Shipping/ShippingCalculator.php` |
| Service | `app/Services/Shipping/ShippingCalculation.php` |
| Service | `app/Services/Setting/EcSettingProvider.php` |
| Enum | `app/Enums/PaymentMethod.php` |
| Test | `tests/Unit/Services/Shipping/ShippingCalculatorTest.php` |

## 受け入れ条件

- しきい値未満では都道府県の送料が適用される: `tests/Unit/Services/Shipping/ShippingCalculatorTest.php`（`しきい値未満のときは都道府県の送料が適用される`）
- しきい値以上・しきい値と同額では送料が0になる: 同上（`しきい値以上のときは送料が無料になる`・`しきい値と同額のときは送料が無料になる`）
- 代引き・銀行振込で代引き手数料が切り替わる: 同上（`代引きのときは代引き手数料が適用される`・`銀行振込のときは代引き手数料が0になる`）
- 配達予定日が当日＋配送予定日数（暦日）になる: 同上（`配達予定日は当日に配送予定日数を暦日で加算した日付になる`）
- 合計が商品合計＋適用送料＋代引き手数料になる: 同上（`合計は商品合計と適用送料と代引き手数料の合算になる`・`送料無料が適用されると合計に送料が含まれない`）
- 確定済み注文の金額が設定変更の影響を受けない: `tests/Feature/Front/Order/OrderSnapshotTest.php`（`送料設定を変更しても注文の送料と配達予定日は変わらない`・`基本設定を変更しても注文のしきい値と手数料と案内文は変わらない`）
