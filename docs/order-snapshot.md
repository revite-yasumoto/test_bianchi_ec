# 注文時スナップショット

本ドキュメントは、注文確定時に `orders` / `order_items` へ複製する**スナップショットの設計方針と全列の値の出所**の正本である。注文を表示・作成するすべての画面（購入手続き・注文完了・管理の注文一覧／注文詳細・マイページの注文履歴）はこの規則に従い、各画面の仕様書は本ドキュメントへリンクして差分のみを記述する。

横断ドキュメントのため `## ソースファイル` を持たない。スナップショットを書き込む実装の正本は [docs/front/checkout.md](front/checkout.md)、テーブル定義の正本は [docs/2_database.md](2_database.md)。

## 設計方針

- 注文は**確定した時点の事実**であり、後から参照先が変わっても内容が変わってはならない。そのため会員・配送先・商品・カテゴリ・送料設定・EC基本設定の値を、参照ではなく**値として複製**して保持する。
- `orders.user_id` / `order_items.product_id` / `order_items.product_variant_id` は、注文履歴の絞り込みや商品ページへの導線のために残す**参照専用の列**であり、表示・金額の根拠には使わない。商品が削除された明細は参照だけが `null` になり、スナップショット列は残る。
- 都道府県は `prefecture_id` ではなく `shipping_prefecture_name`（名称）で保持する。送料設定の付け替え・改名の影響を受けないようにするため。
- 金額はすべて確定時にサーバー側で再計算した結果を保存する。画面・セッションが持っていた金額は保存に使わない。
- 確定後に `orders` / `order_items` のスナップショット列を更新する処理は設けない。書き換えてよいのは `status` / `cancelled_at`（[docs/admin/order-show.md](admin/order-show.md) が正本）のみ。

## `orders` の列と値の出所

| 列 | 値の出所 |
|---|---|
| `order_number` | 採番結果（`BNC-YYMM-NNNN`。規則は [docs/front/checkout.md](front/checkout.md)） |
| `user_id` | ログイン中の会員ID（参照専用） |
| `status` | 支払い方法から決まる初期ステータス（銀行振込＝入金待ち／代引き＝注文受付） |
| `payment_method` | 購入手続きでの選択値 |
| `ordered_at` | 注文確定時の現在日時 |
| `cancelled_at` | 確定時は `null`。キャンセル時に記録する |
| `member_code_snapshot` | `users.member_code` |
| `customer_name` / `customer_name_kana` / `customer_email` / `customer_tel` | `users` の各値 |
| `shipping_recipient_name` / `shipping_postal_code` / `shipping_city` / `shipping_address_line1` / `shipping_address_line2` / `shipping_tel` | 選択した `user_addresses` の各値 |
| `shipping_prefecture_name` | `prefectures.name`（IDではなく名称） |
| `subtotal` | 明細の `unit_price × quantity` の合計 |
| `shipping_fee` | 再計算した適用送料（送料無料適用後） |
| `cod_fee` | 再計算した適用代引き手数料 |
| `total` | `subtotal + shipping_fee + cod_fee` |
| `free_shipping_threshold` | 注文時点の `ec_settings.free_shipping_threshold` |
| `shipping_fee_base` | 注文時点の `shipping_settings.fee`（送料無料適用前の素の送料） |
| `delivery_days` | 注文時点の `shipping_settings.delivery_days` |
| `estimated_delivery_date` | `ordered_at` の日付 + `delivery_days` 日（暦日） |
| `bank_transfer_note` | 銀行振込のとき注文時点の `ec_settings.bank_transfer_note`、代引きのとき `null` |

金額4列と算出根拠4列は [docs/shipping-calculation.md](shipping-calculation.md) の算出結果をそのまま保存したものである。

## `order_items` の列と値の出所

| 列 | 値の出所 |
|---|---|
| `order_id` | 作成した注文 |
| `product_id` / `product_variant_id` | 参照専用。商品削除時は `null` になる |
| `product_code` | `products.product_code` |
| `product_name` | `products.name` |
| `category_name` | `categories.name` |
| `sku_code` / `size_name` / `color_name` | `product_variants` の各値（規格を持たない商品は `null`） |
| `product_image_path` | メイン画像（`product_images.sort_order = 0`）の `path`。なければ `null` |
| `unit_price` | `products.price`（注文時点の価格） |
| `quantity` | `cart_items.quantity` |
| `subtotal` | `unit_price × quantity` |

## 関連ドキュメント

- [docs/front/checkout.md](front/checkout.md) — スナップショットを書き込む注文確定処理の正本
- [docs/front/order-complete.md](front/order-complete.md) — 注文完了画面
- [docs/shipping-calculation.md](shipping-calculation.md) — 金額・算出根拠の算出規則の正本
- [docs/admin/order-show.md](admin/order-show.md) — スナップショットを表示する管理画面と、確定後に変更してよい列（ステータス）の正本
- [docs/admin/order-index.md](admin/order-index.md) — スナップショットを一覧・検索する管理画面
- [docs/2_database.md](2_database.md) — `orders` / `order_items` のテーブル定義の正本
