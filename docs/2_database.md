# データベース設計

全テーブル定義の正本。本ドキュメントは横断ドキュメントではなく、DB全体を対象とする仕様書として `## ソースファイル` を持つ。以降の各機能仕様書はテーブル定義を再掲せず、本ドキュメントへリンクする。

## 機能概要

- **対象:** 全24テーブルのスキーマ（Migration・Enum・Model・Factory・Seeder）
- **本ドキュメントのスコープ:** テーブル定義・リレーション・Enum・初期データのみ。画面・Controller・ルーティングは対象外（各機能の仕様書を参照）

## 設計方針: 注文時スナップショット

`orders` / `order_items` は参照用の外部キー（`user_id` / `product_id` / `product_variant_id`）を持つが、表示・金額計算に使う値はすべて自テーブルの列に複製して保存する。そのため注文一覧・詳細・マイページ注文履歴は `products` / `categories` / `shipping_settings` / `ec_settings` を一切 JOIN せずに描画できる。方針の詳細と全列の値の出所は [docs/order-snapshot.md](order-snapshot.md) が正本。

スキーマ側の裏付けとして、商品・バリエーションが削除されても注文明細は残るよう `product_id` / `product_variant_id` を `ON DELETE SET NULL` とし、会員は削除させないため `orders.user_id` を `ON DELETE RESTRICT` とする。

## MySQL製品制約への対応

- 文字セットは `utf8mb4`（Laravel既定）
- ランキングの順位列は `rank_position`（`RANK` は MySQL 8.0 の予約語のため回避）
- 将来日付を持つ列（`estimated_delivery_date` / `display_start_on` / `display_end_on` / `published_on`）は `date` 型。過去日時のみを持つ列（`ordered_at` / `cancelled_at` / `viewed_at` / `changed_at` / `aggregated_at`）は `dateTime` 型
- 1マイグレーション1テーブル（DDLはロールバック不可のため）
- テスト用DBはSQLite in-memoryのため、上記制約への適合はコード上の確認をもって担保する（テストのグリーンはMySQLでの適合を保証しない）

## テーブル定義

`id` は全テーブル `bigIncrements`（`prefectures` のみ `tinyIncrements`）。`timestamps` は Laravel標準（`created_at` / `updated_at`、`prefectures` のみ持たない）。

### 認証・会員

#### `users`（会員マスタ）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| member_code | string(20) | unique | 会員ID（例 `M-100238`） |
| name | string(100) | | 氏名 |
| name_kana | string(100) | nullable | 氏名カナ |
| email | string(191) | unique | |
| email_verified_at | timestamp | nullable | Laravel標準（メール認証は未使用） |
| password | string(255) | hashed | |
| tel | string(20) | nullable | |
| status | string(20) | default `active`, index | `App\Enums\UserStatus` |
| remember_token | string(100) | nullable | |

#### `admins`（管理者マスタ）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| admin_code | string(20) | unique | 管理者ID（例 `A-001`） |
| name | string(100) | | |
| email | string(191) | unique | |
| password | string(255) | hashed | |
| remember_token | string(100) | nullable | |

権限管理カラムは持たない。

#### `user_addresses`（会員の配送先住所）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| user_id | foreignId | → `users.id` cascade, index | |
| label | string(100) | | 表示名（例 `自宅`） |
| recipient_name | string(100) | | |
| postal_code | string(8) | | |
| prefecture_id | unsignedTinyInteger | → `prefectures.id` restrict | |
| city | string(100) | | |
| address_line1 | string(255) | | |
| address_line2 | string(255) | nullable | |
| tel | string(20) | | |
| is_default | boolean | default `false` | |

### マスタ

#### `prefectures`（都道府県マスタ・不変）

| カラム | 型 | 制約 |
|---|---|---|
| id | tinyIncrements | PK。JIS X 0401順 1〜47 |
| name | string(10) | unique |

#### `shipping_settings`（送料設定マスタ）

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| prefecture_id | unsignedTinyInteger | unique, → `prefectures.id` cascade |
| fee | unsignedInteger | 送料（円） |
| delivery_days | unsignedTinyInteger | 配送予定日数 |

初期値: 北海道 1,000円/5日、沖縄県 1,000円/6日、その他 500円/3日。

#### `categories`（カテゴリ管理）

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| name | string(50) | unique |
| sort_order | unsignedInteger | default `0` |

#### `spec_options`（規格管理：サイズ・カラー）

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| type | string(10) | `App\Enums\SpecOptionType`（`size` / `color`） |
| name | string(50) | 規格値（例 `S`、`アクア`） |
| sort_order | unsignedInteger | default `0` |

索引: `unique(type, name)`

#### `ec_settings`（EC基本設定・単一行、id=1）

| カラム | 型 | 既定値 |
|---|---|---|
| id | bigIncrements | PK |
| free_shipping_threshold | unsignedInteger | `10000` |
| cod_fee | unsignedInteger | `330` |
| bank_transfer_note | text | |

### 商品

#### `products`（商品マスタ）

在庫カラムは持たない（在庫マスタに一元化）。

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| product_code | string(50) | unique。ユーザー入力の商品ID（SKUコードのベース） |
| name | string(255) | |
| category_id | foreignId | → `categories.id` restrict, index |
| price | unsignedInteger | 税込 |
| description | text | nullable |
| has_sku | boolean | default `false` |
| is_published | boolean | default `false`, index |

#### `product_images`

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| product_id | foreignId | → `products.id` cascade |
| path | string(255) | |
| sort_order | unsignedTinyInteger | `0`=メイン、`1`〜`9`=サブ |

索引: `unique(product_id, sort_order)`

#### `product_specs`（商品スペック表）

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| product_id | foreignId | → `products.id` cascade, index |
| label | string(100) | 項目名 |
| value | string(255) | |
| sort_order | unsignedInteger | default `0` |

#### `product_variants`（SKU）

SKUなし商品も `size_name` / `color_name` を `null` としたバリエーション1件を必ず持つ。

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| product_id | foreignId | → `products.id` cascade, index |
| branch_code | string(20) | nullable。枝番（SKUあり商品のみ） |
| sku_code | string(80) | nullable, unique。SKUあり=`商品ID-枝番`、SKUなし=商品IDそのもの。取扱対象外は `null` |
| size_name | string(50) | nullable |
| color_name | string(50) | nullable |
| is_available | boolean | default `true`。`false`=規格なし（取扱対象外） |

索引: `unique(product_id, size_name, color_name)`

#### `stocks`（在庫マスタ）

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| product_variant_id | foreignId | unique, → `product_variants.id` cascade |
| quantity | unsignedInteger | default `0`。`0`=在庫切れ |

### カート・お気に入り・閲覧履歴

#### `cart_items`

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| user_id | foreignId | → `users.id` cascade |
| product_variant_id | foreignId | → `product_variants.id` cascade |
| quantity | unsignedInteger | |

索引: `unique(user_id, product_variant_id)`

#### `favorites`

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| user_id | foreignId | → `users.id` cascade |
| product_id | foreignId | → `products.id` cascade |

索引: `unique(user_id, product_id)`

#### `browsing_histories`（閲覧履歴。ログイン会員のみ）

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| user_id | foreignId | → `users.id` cascade |
| product_id | foreignId | → `products.id` cascade |
| viewed_at | dateTime | 最終閲覧日時 |

索引: `unique(user_id, product_id)`, `index(user_id, viewed_at)`

### 注文（スナップショット）

#### `orders`

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| order_number | string(30) | unique | 例 `BNC-2608-1042` |
| user_id | foreignId | → `users.id` restrict, index | 参照用 |
| status | string(20) | index | `App\Enums\OrderStatus` |
| payment_method | string(20) | | `App\Enums\PaymentMethod` |
| ordered_at | dateTime | index | |
| cancelled_at | dateTime | nullable | |
| member_code_snapshot | string(20) | | 注文時点の会員ID |
| customer_name / customer_name_kana / customer_email / customer_tel | string | name_kana・telはnullable | 注文時点の会員情報 |
| shipping_recipient_name / shipping_postal_code / shipping_prefecture_name / shipping_city / shipping_address_line1 / shipping_address_line2 / shipping_tel | string | address_line2はnullable | 配送先スナップショット。`shipping_prefecture_name` はIDでなく名称を保存 |
| subtotal / shipping_fee / total | unsignedInteger | | 金額スナップショット |
| cod_fee | unsignedInteger | default `0` | |
| free_shipping_threshold / shipping_fee_base | unsignedInteger | | 算出根拠スナップショット |
| delivery_days | unsignedTinyInteger | | |
| estimated_delivery_date | date | | `ordered_at` + `delivery_days` 日 |
| bank_transfer_note | text | nullable | 銀行振込のとき注文時点の案内文 |

#### `order_items`

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| order_id | foreignId | → `orders.id` cascade, index | |
| product_id | foreignId | nullable, → `products.id` **set null** | 参照用 |
| product_variant_id | foreignId | nullable, → `product_variants.id` **set null** | 参照用 |
| product_code / product_name / category_name | string | | 注文時点の商品情報 |
| sku_code / size_name / color_name / product_image_path | string | 全てnullable | 注文時点のSKU情報 |
| unit_price | unsignedInteger | | 注文時点の単価 |
| quantity / subtotal | unsignedInteger | | |

#### `order_status_histories`

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| order_id | foreignId | → `orders.id` cascade, index |
| admin_id | foreignId | nullable, → `admins.id` set null。会員のキャンセル依頼は `null` |
| from_status | string(20) | nullable |
| to_status | string(20) | |
| changed_at | dateTime | |

### ニュース・お知らせ・ランキング・その他

#### `news`（新着ニュース）

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| published_on | date | 掲載日 |
| category | string(20) | `App\Enums\NewsCategory`（`新商品` / `お知らせ` / `商品情報`） |
| title | string(255) | |
| body | text | |
| is_published | boolean | default `true` |

索引: `index(is_published, published_on)`。`News::published()` スコープが `is_published=true` に絞り込む。

#### `notices`（重要なお知らせ）

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| title | string(255) | |
| body | text | |
| display_start_on | date | |
| display_end_on | date | |

索引: `index(display_start_on, display_end_on)`。掲載状態はカラムを持たず、`Notice::displayable()` スコープと `Notice::state()` が当日日付と掲載期間から算出する。算出規則は [docs/admin/notice.md](admin/notice.md) が正本。

#### `product_rankings`

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| target_year_month | char(7) | 例 `2026-07` |
| category_id | foreignId | nullable, → `categories.id` cascade。`null`=全体ランキング |
| product_id | foreignId | → `products.id` cascade |
| rank_position | unsignedTinyInteger | 順位 |
| aggregated_at | dateTime | 集計実行日時 |

索引: `unique(target_year_month, category_id, rank_position)`, `index(target_year_month, category_id)`

#### `banners`（TOPメインビジュアル）

管理UIは要件の10メニューに含まれないため作らない。`BannerSeeder` で投入する。

| カラム | 型 | 制約・既定値 |
|---|---|---|
| id | bigIncrements | PK |
| tag | string(50) | |
| title | string(255) | 改行を含む |
| subtitle | string(255) | nullable |
| background | string(255) | 背景スタイル値（例 `linear-gradient(...)`） |
| link_url | string(255) | nullable |
| sort_order | unsignedInteger | default `0` |
| is_active | boolean | default `true` |

#### `contacts`（お問い合わせ）

| カラム | 型 | 制約 |
|---|---|---|
| id | bigIncrements | PK |
| user_id | foreignId | nullable, → `users.id` set null |
| name | string(100) | |
| email | string(191) | |
| product_name | string(255) | nullable |
| body | text | |

## Enum一覧（`app/Enums/`）

| Enum | 値 | 用途 |
|---|---|---|
| `OrderStatus` | `received` / `awaiting_payment` / `payment_confirmed` / `preparing` / `shipped` / `cancelled` | `label()` で日本語表示名、`color()` でバッジ配色、`allowedTransitions()` / `canTransitionTo()` で遷移規則を提供 |
| `PaymentMethod` | `bank_transfer` / `cod` | `label()` で日本語表示名、`initialOrderStatus()` で注文確定時の初期ステータス（銀行振込＝入金待ち／代引き＝注文受付）を提供 |
| `UserStatus` | `active` / `suspended` / `withdrawn` | `label()` で日本語表示名。`withdrawn` を付けられるのは会員自身の退会のみ |
| `SpecOptionType` | `size` / `color` | `label()` で日本語表示名 |
| `NewsCategory` | `新商品` / `お知らせ` / `商品情報` | `label()` / `color()` |

## 業務ルール

- Seeder投入の管理者パスワードは環境変数 `ADMIN_SEED_PASSWORD`（`config('app.admin_seed_password')`）から読み、コードに直書きしない。未設定時のフォールバックは `password`（開発用）。
- `DatabaseSeeder` は各テーブルの投入後に商品ランキングの集計を実行する。ランキングは注文の集計結果であり、注文の初期データだけでは `product_rankings` が空のままTOPのランキングが出ないため、`db:seed` だけでデモに必要なデータが揃うようにする。集計対象月は投入した注文の月に合わせて基準日を明示的に渡す（規則は [docs/ranking.md](ranking.md) が正本）。

## 関連ドキュメント

- [docs/1_system_overview.md](1_system_overview.md) — 技術構成・認証前提・共通業務ルール
- [docs/order-snapshot.md](order-snapshot.md) — 注文時スナップショットの設計方針と全列の値の出所の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Migration | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Migration | `database/migrations/2026_08_07_000001_create_admins_table.php` |
| Migration | `database/migrations/2026_08_07_000002_create_prefectures_table.php` |
| Migration | `database/migrations/2026_08_07_000003_create_shipping_settings_table.php` |
| Migration | `database/migrations/2026_08_07_000004_create_user_addresses_table.php` |
| Migration | `database/migrations/2026_08_07_000005_create_categories_table.php` |
| Migration | `database/migrations/2026_08_07_000006_create_spec_options_table.php` |
| Migration | `database/migrations/2026_08_07_000007_create_ec_settings_table.php` |
| Migration | `database/migrations/2026_08_07_000008_create_products_table.php` |
| Migration | `database/migrations/2026_08_07_000009_create_product_images_table.php` |
| Migration | `database/migrations/2026_08_07_000010_create_product_specs_table.php` |
| Migration | `database/migrations/2026_08_07_000011_create_product_variants_table.php` |
| Migration | `database/migrations/2026_08_07_000012_create_stocks_table.php` |
| Migration | `database/migrations/2026_08_07_000013_create_cart_items_table.php` |
| Migration | `database/migrations/2026_08_07_000014_create_favorites_table.php` |
| Migration | `database/migrations/2026_08_07_000015_create_browsing_histories_table.php` |
| Migration | `database/migrations/2026_08_07_000016_create_orders_table.php` |
| Migration | `database/migrations/2026_08_07_000017_create_order_items_table.php` |
| Migration | `database/migrations/2026_08_07_000018_create_order_status_histories_table.php` |
| Migration | `database/migrations/2026_08_07_000019_create_news_table.php` |
| Migration | `database/migrations/2026_08_07_000020_create_notices_table.php` |
| Migration | `database/migrations/2026_08_07_000021_create_product_rankings_table.php` |
| Migration | `database/migrations/2026_08_07_000022_create_banners_table.php` |
| Migration | `database/migrations/2026_08_07_000023_create_contacts_table.php` |
| Enum | `app/Enums/OrderStatus.php` |
| Enum | `app/Enums/PaymentMethod.php` |
| Enum | `app/Enums/UserStatus.php` |
| Enum | `app/Enums/SpecOptionType.php` |
| Enum | `app/Enums/NewsCategory.php` |
| Model | `app/Models/User.php` |
| Model | `app/Models/Admin.php` |
| Model | `app/Models/Prefecture.php` |
| Model | `app/Models/ShippingSetting.php` |
| Model | `app/Models/UserAddress.php` |
| Model | `app/Models/Category.php` |
| Model | `app/Models/SpecOption.php` |
| Model | `app/Models/EcSetting.php` |
| Model | `app/Models/Product.php` |
| Model | `app/Models/ProductImage.php` |
| Model | `app/Models/ProductSpec.php` |
| Model | `app/Models/ProductVariant.php` |
| Model | `app/Models/Stock.php` |
| Model | `app/Models/CartItem.php` |
| Model | `app/Models/Favorite.php` |
| Model | `app/Models/BrowsingHistory.php` |
| Model | `app/Models/Order.php` |
| Model | `app/Models/OrderItem.php` |
| Model | `app/Models/OrderStatusHistory.php` |
| Model | `app/Models/News.php` |
| Model | `app/Models/Notice.php` |
| Model | `app/Models/ProductRanking.php` |
| Model | `app/Models/Banner.php` |
| Model | `app/Models/Contact.php` |
| Factory | `database/factories/UserFactory.php` |
| Factory | `database/factories/AdminFactory.php` |
| Factory | `database/factories/PrefectureFactory.php` |
| Factory | `database/factories/ShippingSettingFactory.php` |
| Factory | `database/factories/UserAddressFactory.php` |
| Factory | `database/factories/CategoryFactory.php` |
| Factory | `database/factories/SpecOptionFactory.php` |
| Factory | `database/factories/EcSettingFactory.php` |
| Factory | `database/factories/ProductFactory.php` |
| Factory | `database/factories/ProductImageFactory.php` |
| Factory | `database/factories/ProductSpecFactory.php` |
| Factory | `database/factories/ProductVariantFactory.php` |
| Factory | `database/factories/StockFactory.php` |
| Factory | `database/factories/CartItemFactory.php` |
| Factory | `database/factories/FavoriteFactory.php` |
| Factory | `database/factories/BrowsingHistoryFactory.php` |
| Factory | `database/factories/OrderFactory.php` |
| Factory | `database/factories/OrderItemFactory.php` |
| Factory | `database/factories/OrderStatusHistoryFactory.php` |
| Factory | `database/factories/NewsFactory.php` |
| Factory | `database/factories/NoticeFactory.php` |
| Factory | `database/factories/ProductRankingFactory.php` |
| Factory | `database/factories/BannerFactory.php` |
| Factory | `database/factories/ContactFactory.php` |
| Seeder | `database/seeders/DatabaseSeeder.php` |
| Seeder | `database/seeders/PrefectureSeeder.php` |
| Seeder | `database/seeders/ShippingSettingSeeder.php` |
| Seeder | `database/seeders/EcSettingSeeder.php` |
| Seeder | `database/seeders/CategorySeeder.php` |
| Seeder | `database/seeders/SpecOptionSeeder.php` |
| Seeder | `database/seeders/AdminSeeder.php` |
| Seeder | `database/seeders/ProductSeeder.php` |
| Seeder | `database/seeders/UserSeeder.php` |
| Seeder | `database/seeders/OrderSeeder.php` |
| Seeder | `database/seeders/NewsSeeder.php` |
| Seeder | `database/seeders/NoticeSeeder.php` |
| Seeder | `database/seeders/BannerSeeder.php` |
| 設定 | `config/auth.php` |
| 設定 | `config/app.php` |
| Test | `tests/Feature/Database/SchemaTest.php` |
| Test | `tests/Feature/Database/OrderSnapshotTest.php` |
| Test | `tests/Feature/Database/SeederTest.php` |
| Test | `tests/Unit/Enums/OrderStatusTest.php` |

## 受け入れ条件

- 全24テーブルが作成され、主要な一意制約・外部キーが機能する: `tests/Feature/Database/SchemaTest.php` で担保（テーブル存在・一意制約違反・カスケード削除・削除制限を検証）
- 注文確定後に商品価格・商品名・カテゴリ名・会員情報・送料設定・EC基本設定を変更しても `orders` / `order_items` のスナップショット列が変わらない。商品を削除しても注文明細が残り `product_id` が `null` になる: `tests/Feature/Database/OrderSnapshotTest.php` で担保
- Seeder実行後に都道府県47件・送料設定47件・EC基本設定1件が存在し、初期値が正しい。`DatabaseSeeder` が全ステータスを網羅する注文を投入する。Seederは再実行しても行が重複しない（冪等）: `tests/Feature/Database/SeederTest.php` で担保
- Seeder実行後に前月のランキングが全体・カテゴリ別の双方で作られ、順位が1から連番で付き、販売数の最も多い商品が1位になる: 同上（`シード実行後に前月のランキングが全体とカテゴリ別で作られる`・`ランキングの順位は一位から連番で付く`・`販売数の最も多い商品が全体ランキングの一位になる`）
- 全体のSeederを2回実行しても注文とランキングが重複しない: 同上（`シードを二回実行しても注文とランキングが重複しない`）
- `OrderStatus` の `label()` が各ステータスの日本語表示名を返し、`allowedTransitions()` / `canTransitionTo()` が終端ステータス（出荷済み・キャンセル）からの遷移を許可しない: `tests/Unit/Enums/OrderStatusTest.php` で担保
- 未確定: `php artisan migrate` の実行（本番相当のMySQLへのスキーマ反映）はユーザー承認前のため未実施。現状の実装はテスト用SQLite in-memory上でのみ検証済み。
