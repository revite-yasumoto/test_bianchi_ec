# 単位01: DB設計（全テーブル・Model・Seeder）

全単位の土台。マイグレーション・Eloquent Model・マスタ初期値の Seeder のみを作成し、画面・Controller は作らない。

## スコープ

- 本ECサイトで使う全テーブルのマイグレーションを作成する
- 全 Model（リレーション・`$fillable`・`$casts`・Enum キャスト）を作成する
- マスタの初期値 Seeder（都道府県47件・送料設定47件・EC基本設定1件）とデモデータ Seeder を作成する
- 画面・Controller・ルートは本単位のスコープ外

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| マイグレーション・Model の実装（DBアクセスを含む） | `db-mysql:mysql` |
| `.php` ファイル全般 | `laravel-set:laravel` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## 設計の中心方針: 注文時スナップショット

注文後に商品価格・商品名・カテゴリ名・会員情報・住所・送料設定・EC基本設定が変更されても、過去の注文の表示・再計算結果が一切変わらないようにする。

- `orders` / `order_items` は参照用の外部キー（`user_id` / `product_id` / `product_variant_id`）を持つが、**表示・金額計算に使う値はすべて自テーブルの列に複製して保存する**
- 商品・バリエーションが削除されても注文は残す（`product_id` / `product_variant_id` は `ON DELETE SET NULL`）
- 会員は削除させない（`user_id` は `ON DELETE RESTRICT`）
- 金額の**算出根拠**（適用した送料無料しきい値・都道府県の素の送料・配送日数・振込案内文）も保存し、注文時点の計算を後から再現できるようにする
- 注文一覧・注文詳細・マイページ注文履歴は、`products` / `categories` / `shipping_settings` / `ec_settings` を一切 JOIN せずに描画できる

## MySQL 製品制約への対応（`db-mysql:mysql` 適用結果）

- 文字セットは `utf8mb4`（Laravel 既定を使用。`utf8` / `utf8mb3` を指定しない）
- **予約語回避**: ランキングの順位列は `rank` ではなく `rank_position` とする（`RANK` は MySQL 8.0 の予約語）
- **索引接頭辞長**: 一意制約を張る文字列列は `email` を `VARCHAR(191)`（764バイト）に抑える。`product_code`(50) / `sku_code`(80) / `order_number`(30) / `member_code`(20) / `admin_code`(20) はいずれも COMPACT 行フォーマットの767バイト上限にも収まる
- **日時型**: 将来日付を持ちうる列は `date` 型（`estimated_delivery_date` / `display_start_on` / `display_end_on` / `published_on`）とし `timestamp` を使わない。過去日時のみを持つ列（`ordered_at` / `cancelled_at` / `viewed_at` / `aggregated_at`）は `dateTime` で統一する
- **DDLの粒度**: 1マイグレーション1テーブルとし、複数の `CREATE TABLE` を1ファイルに並べない（DDLはロールバックできないため）
- テスト用DBが SQLite であるため、上記の制約への適合はコード上の確認をもって報告する。テストのグリーンを根拠に「MySQL で問題なし」と報告しない

## テーブル定義

`id` は全テーブル `bigIncrements`（`prefectures` のみ `tinyIncrements`）。`timestamps` は Laravel 標準（`created_at` / `updated_at`）。

### 1. 認証・会員

#### `users`（会員マスタ）- 既存マイグレーションを書き換え

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| member_code | string(20) | unique | 会員ID（例 `M-100238`） |
| name | string(100) | | 氏名 |
| name_kana | string(100) | nullable | 氏名カナ |
| email | string(191) | unique | メールアドレス |
| email_verified_at | timestamp | nullable | Laravel 標準（メール認証は本デモでは未使用） |
| password | string(255) | | ハッシュ |
| tel | string(20) | nullable | 電話番号 |
| status | string(20) | default `active` | `active`（有効）/ `suspended`（休会） |
| remember_token | string(100) | nullable | |
| timestamps | | | 登録日は `created_at` を使う |

索引: `index(status)`

既存の `0001_01_01_000000_create_users_table.php` を書き換える（`sessions` / `password_reset_tokens` テーブル定義はそのまま残す）。

#### `admins`（管理者マスタ）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| admin_code | string(20) | unique | 管理者ID（例 `A-001`） |
| name | string(100) | | 氏名 |
| email | string(191) | unique | メールアドレス |
| password | string(255) | | ハッシュ |
| remember_token | string(100) | nullable | |
| timestamps | | | |

権限管理カラムは持たない（要件で対象外）。

#### `user_addresses`（会員の配送先住所）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| user_id | foreignId | → `users.id` cascade | |
| label | string(100) | | 表示名（例 `自宅`） |
| recipient_name | string(100) | | 宛名 |
| postal_code | string(8) | | 郵便番号（ハイフンあり） |
| prefecture_id | foreignId(tinyInteger) | → `prefectures.id` restrict | 送料算出のキー |
| city | string(100) | | 市区町村 |
| address_line1 | string(255) | | 番地 |
| address_line2 | string(255) | nullable | 建物名・部屋番号 |
| tel | string(20) | | |
| is_default | boolean | default `false` | 既定の配送先 |
| timestamps | | | |

索引: `index(user_id)`

### 2. マスタ

#### `prefectures`（都道府県マスタ）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | tinyIncrements | PK | JIS X 0401 順の 1〜47 |
| name | string(10) | unique | 都道府県名 |

`timestamps` を持たない（不変マスタ）。

#### `shipping_settings`（送料設定マスタ）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| prefecture_id | foreignId(tinyInteger) | unique, → `prefectures.id` cascade | |
| fee | unsignedInteger | | 送料（円） |
| delivery_days | unsignedTinyInteger | | 配送予定日数 |
| timestamps | | | |

初期値: 北海道・沖縄県 `fee=1000, delivery_days=5`（沖縄県は6）、その他 `fee=500, delivery_days=3`。

#### `categories`（カテゴリ管理）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| name | string(50) | unique | カテゴリ名 |
| sort_order | unsignedInteger | default `0` | 表示順 |
| timestamps | | | |

#### `spec_options`（規格管理：サイズ・カラー）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| type | string(10) | | `size` / `color` |
| name | string(50) | | 規格値（例 `S` / `アクア`） |
| sort_order | unsignedInteger | default `0` | 表示順 |
| timestamps | | | |

索引: `unique(type, name)`

#### `ec_settings`（EC基本設定）

単一行テーブル（`id=1` のみを使う）。

| カラム | 型 | 既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| free_shipping_threshold | unsignedInteger | `10000` | 送料無料となる購入金額（税込） |
| cod_fee | unsignedInteger | `330` | 代引き手数料 |
| bank_transfer_note | text | | 銀行振込の案内文 |
| timestamps | | | |

### 3. 商品

#### `products`（商品マスタ）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| product_code | string(50) | unique | ユーザー入力の商品ID（SKUコードのベース。例 `RC7-105`） |
| name | string(255) | | 商品名 |
| category_id | foreignId | → `categories.id` restrict | |
| price | unsignedInteger | | 価格（税込） |
| description | text | nullable | 商品説明 |
| has_sku | boolean | default `false` | SKUの有無 |
| is_published | boolean | default `false` | 公開状態 |
| timestamps | | | |

索引: `index(category_id)`, `index(is_published)`

在庫カラムは持たない（在庫は在庫マスタに一元化する要件）。

#### `product_images`

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| product_id | foreignId | → `products.id` cascade | |
| path | string(255) | | ストレージ上のパス |
| sort_order | unsignedTinyInteger | | `0`=メイン画像、`1`〜`9`=サブ画像 |
| timestamps | | | |

索引: `unique(product_id, sort_order)`

#### `product_specs`（商品スペック表）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| product_id | foreignId | → `products.id` cascade | |
| label | string(100) | | 項目名（例 `フレーム`） |
| value | string(255) | | 内容 |
| sort_order | unsignedInteger | default `0` | 表示順 |
| timestamps | | | |

索引: `index(product_id)`

#### `product_variants`（SKU）

SKUなし商品も `size_name` / `color_name` を `null` としたバリエーション1件を必ず持つ。これにより在庫・カート・注文明細がSKUの有無で分岐しない。

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| product_id | foreignId | → `products.id` cascade | |
| branch_code | string(20) | nullable | 枝番（SKUあり商品のみ） |
| sku_code | string(80) | nullable, unique | `商品ID-枝番`。取扱対象外・SKUなしは `null` |
| size_name | string(50) | nullable | サイズ |
| color_name | string(50) | nullable | カラー |
| is_available | boolean | default `true` | `false` = 「規格なし（取扱対象外）」 |
| timestamps | | | |

索引: `unique(product_id, size_name, color_name)`, `index(product_id)`

#### `stocks`（在庫マスタ）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| product_variant_id | foreignId | unique, → `product_variants.id` cascade | |
| quantity | unsignedInteger | default `0` | 在庫数。`0` = 在庫切れ |
| timestamps | | | |

### 4. カート・お気に入り・閲覧履歴

#### `cart_items`

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| user_id | foreignId | → `users.id` cascade | |
| product_variant_id | foreignId | → `product_variants.id` cascade | |
| quantity | unsignedInteger | | |
| timestamps | | | |

索引: `unique(user_id, product_variant_id)`

#### `favorites`

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| user_id | foreignId | → `users.id` cascade | |
| product_id | foreignId | → `products.id` cascade | |
| timestamps | | | |

索引: `unique(user_id, product_id)`

#### `browsing_histories`（閲覧履歴）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| user_id | foreignId | → `users.id` cascade | |
| product_id | foreignId | → `products.id` cascade | |
| viewed_at | dateTime | | 最終閲覧日時 |
| timestamps | | | |

索引: `unique(user_id, product_id)`, `index(user_id, viewed_at)`

### 5. 注文（スナップショット）

#### `orders`

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| order_number | string(30) | unique | 注文番号（例 `BNC-2608-1042`） |
| user_id | foreignId | → `users.id` restrict | 参照用 |
| status | string(20) | | 下記ステータス |
| payment_method | string(20) | | `bank_transfer` / `cod` |
| ordered_at | dateTime | | 注文日時 |
| cancelled_at | dateTime | nullable | キャンセル日時 |
| **会員スナップショット** | | | |
| member_code_snapshot | string(20) | | 注文時点の会員ID |
| customer_name | string(100) | | 注文時点の氏名 |
| customer_name_kana | string(100) | nullable | 注文時点の氏名カナ |
| customer_email | string(191) | | 注文時点のメールアドレス |
| customer_tel | string(20) | nullable | 注文時点の電話番号 |
| **配送先スナップショット** | | | |
| shipping_recipient_name | string(100) | | |
| shipping_postal_code | string(8) | | |
| shipping_prefecture_name | string(10) | | 都道府県名（IDではなく名称を保存） |
| shipping_city | string(100) | | |
| shipping_address_line1 | string(255) | | |
| shipping_address_line2 | string(255) | nullable | |
| shipping_tel | string(20) | | |
| **金額スナップショット** | | | |
| subtotal | unsignedInteger | | 商品合計 |
| shipping_fee | unsignedInteger | | 適用送料（無料適用後） |
| cod_fee | unsignedInteger | default `0` | 適用代引き手数料 |
| total | unsignedInteger | | 最終合計 |
| **算出根拠スナップショット** | | | |
| free_shipping_threshold | unsignedInteger | | 注文時点の送料無料しきい値 |
| shipping_fee_base | unsignedInteger | | 注文時点の都道府県の素の送料 |
| delivery_days | unsignedTinyInteger | | 注文時点の配送予定日数 |
| estimated_delivery_date | date | | 配達予定日（`ordered_at` + `delivery_days`） |
| bank_transfer_note | text | nullable | 注文時点の振込案内文（`payment_method=bank_transfer` のとき保存） |
| timestamps | | | |

索引: `index(user_id)`, `index(status)`, `index(ordered_at)`

ステータス（`App\Enums\OrderStatus`）:

| 値 | 表示 |
|---|---|
| `received` | 注文受付 |
| `awaiting_payment` | 入金待ち |
| `payment_confirmed` | 入金確認済み |
| `preparing` | 出荷準備中 |
| `shipped` | 出荷済み |
| `cancelled` | キャンセル |

#### `order_items`

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| order_id | foreignId | → `orders.id` cascade | |
| product_id | foreignId | nullable, → `products.id` **set null** | 参照用 |
| product_variant_id | foreignId | nullable, → `product_variants.id` **set null** | 参照用 |
| **商品スナップショット** | | | |
| product_code | string(50) | | 注文時点の商品ID |
| product_name | string(255) | | 注文時点の商品名 |
| category_name | string(50) | | 注文時点のカテゴリ名 |
| sku_code | string(80) | nullable | 注文時点のSKUコード |
| size_name | string(50) | nullable | 注文時点のサイズ |
| color_name | string(50) | nullable | 注文時点のカラー |
| product_image_path | string(255) | nullable | 注文時点のメイン画像パス |
| unit_price | unsignedInteger | | 注文時点の単価 |
| quantity | unsignedInteger | | 数量 |
| subtotal | unsignedInteger | | `unit_price × quantity` |
| timestamps | | | |

索引: `index(order_id)`

#### `order_status_histories`（ステータス変更履歴）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| order_id | foreignId | → `orders.id` cascade | |
| admin_id | foreignId | nullable, → `admins.id` set null | 変更した管理者（会員のキャンセル依頼は `null`） |
| from_status | string(20) | nullable | 変更前 |
| to_status | string(20) | | 変更後 |
| changed_at | dateTime | | |
| timestamps | | | |

索引: `index(order_id)`

### 6. ニュース・お知らせ・ランキング・その他

#### `news`（新着ニュース）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| published_on | date | | 掲載日 |
| category | string(20) | | `新商品` / `お知らせ` / `商品情報` |
| title | string(255) | | |
| body | text | | |
| is_published | boolean | default `true` | 公開／非公開 |
| timestamps | | | |

索引: `index(is_published, published_on)`

#### `notices`（重要なお知らせ）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| title | string(255) | | |
| body | text | | |
| display_start_on | date | | 掲載開始日 |
| display_end_on | date | | 掲載終了日 |
| timestamps | | | |

索引: `index(display_start_on, display_end_on)`

掲載状態（掲載中／予約／掲載終了）は掲載期間と当日日付から算出し、カラムとして保存しない。

#### `product_rankings`

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| target_year_month | char(7) | | 集計対象月（例 `2026-07`） |
| category_id | foreignId | nullable, → `categories.id` cascade | `null` = 全体ランキング |
| product_id | foreignId | → `products.id` cascade | |
| rank_position | unsignedTinyInteger | | 順位（`rank` は MySQL 8.0 の予約語のため使わない） |
| aggregated_at | dateTime | | 集計実行日時 |
| timestamps | | | |

索引: `unique(target_year_month, category_id, rank_position)`, `index(target_year_month, category_id)`

#### `banners`（TOP メインビジュアル）

| カラム | 型 | 制約・既定値 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| tag | string(50) | | 上部の小ラベル |
| title | string(255) | | 見出し（改行を含む） |
| subtitle | string(255) | nullable | 補足 |
| background | string(255) | | 背景スタイル値 |
| link_url | string(255) | nullable | 遷移先 |
| sort_order | unsignedInteger | default `0` | 表示順 |
| is_active | boolean | default `true` | |
| timestamps | | | |

管理UIは要件の10メニューに含まれないため作らない。Seeder で投入する。

#### `contacts`（お問い合わせ）

| カラム | 型 | 制約 | 内容 |
|---|---|---|---|
| id | bigIncrements | PK | |
| user_id | foreignId | nullable, → `users.id` set null | ログイン中の会員 |
| name | string(100) | | |
| email | string(191) | | |
| product_name | string(255) | nullable | 対象商品（商品詳細から自動入力） |
| body | text | | |
| timestamps | | | |

## 新規作成・修正ファイル一覧

### マイグレーション（`database/migrations/`）

| ファイル | 内容 |
|---|---|
| `0001_01_01_000000_create_users_table.php` | **修正**: `users` に `member_code` / `name_kana` / `tel` / `status` を追加。`sessions`・`password_reset_tokens` はそのまま |
| `*_create_admins_table.php` | 新規 |
| `*_create_prefectures_table.php` | 新規 |
| `*_create_shipping_settings_table.php` | 新規 |
| `*_create_user_addresses_table.php` | 新規 |
| `*_create_categories_table.php` | 新規 |
| `*_create_spec_options_table.php` | 新規 |
| `*_create_ec_settings_table.php` | 新規 |
| `*_create_products_table.php` | 新規 |
| `*_create_product_images_table.php` | 新規 |
| `*_create_product_specs_table.php` | 新規 |
| `*_create_product_variants_table.php` | 新規 |
| `*_create_stocks_table.php` | 新規 |
| `*_create_cart_items_table.php` | 新規 |
| `*_create_favorites_table.php` | 新規 |
| `*_create_browsing_histories_table.php` | 新規 |
| `*_create_orders_table.php` | 新規 |
| `*_create_order_items_table.php` | 新規 |
| `*_create_order_status_histories_table.php` | 新規 |
| `*_create_news_table.php` | 新規 |
| `*_create_notices_table.php` | 新規 |
| `*_create_product_rankings_table.php` | 新規 |
| `*_create_banners_table.php` | 新規 |
| `*_create_contacts_table.php` | 新規 |

外部キー制約が依存関係の順に張れるよう、ファイル名のタイムスタンプは上表の順とする。

### Enum（`app/Enums/`）

| ファイル | 内容 |
|---|---|
| `OrderStatus.php` | 6ステータス。`label()` で日本語表示名、`color()` でバッジ配色を返す |
| `PaymentMethod.php` | `bank_transfer` / `cod`。`label()` で日本語表示名 |
| `UserStatus.php` | `active` / `suspended`。`label()` で日本語表示名 |
| `SpecOptionType.php` | `size` / `color` |
| `NewsCategory.php` | `新商品` / `お知らせ` / `商品情報` |

### Model（`app/Models/`）

| ファイル | 主なリレーション |
|---|---|
| `User.php` | **修正**: `addresses` / `cartItems` / `favorites` / `browsingHistories` / `orders` |
| `Admin.php` | `Authenticatable` を継承。`orderStatusHistories` |
| `Prefecture.php` | `shippingSetting` |
| `ShippingSetting.php` | `prefecture` |
| `UserAddress.php` | `user` / `prefecture` |
| `Category.php` | `products` / `rankings` |
| `SpecOption.php` | - |
| `EcSetting.php` | - |
| `Product.php` | `category` / `images` / `mainImage` / `specs` / `variants` / `favorites`。`hasStock()` で在庫二値を判定 |
| `ProductImage.php` | `product` |
| `ProductSpec.php` | `product` |
| `ProductVariant.php` | `product` / `stock` |
| `Stock.php` | `variant` |
| `CartItem.php` | `user` / `variant` |
| `Favorite.php` | `user` / `product` |
| `BrowsingHistory.php` | `user` / `product` |
| `Order.php` | `user` / `items` / `statusHistories` |
| `OrderItem.php` | `order` / `product` / `variant` |
| `OrderStatusHistory.php` | `order` / `admin` |
| `News.php` | スコープ `published()` |
| `Notice.php` | スコープ `displayable()`（掲載期間内） |
| `ProductRanking.php` | `product` / `category` |
| `Banner.php` | スコープ `active()` |
| `Contact.php` | `user` |

### Factory（`database/factories/`）

各 Model に対応する Factory を作成する（テストで使うため）。テストデータは架空値のみを使う。

### Seeder（`database/seeders/`）

| ファイル | 内容 |
|---|---|
| `PrefectureSeeder.php` | 47都道府県（JIS X 0401 順） |
| `ShippingSettingSeeder.php` | 北海道 1,000円/5日、沖縄県 1,000円/6日、その他 500円/3日 |
| `EcSettingSeeder.php` | しきい値 10,000円／代引き手数料 330円／振込案内文 |
| `CategorySeeder.php` | ロードバイク / MTB / シティ / eバイク / パーツ / アパレル |
| `SpecOptionSeeder.php` | サイズ `S,M,L,XL,600ml` ／ カラー `アクア,ブラック,ホワイト,レッド` |
| `AdminSeeder.php` | 管理者4件（`A-001`〜`A-004`）。パスワードは環境変数から読み、コードに直書きしない |
| `ProductSeeder.php` | モックの商品9件（SKUあり3件・なし6件）＋バリエーション＋在庫＋スペック |
| `UserSeeder.php` | 会員6件＋配送先住所 |
| `OrderSeeder.php` | 注文7件（全ステータスを網羅）＋明細（スナップショット列を埋める） |
| `NewsSeeder.php` | 新着ニュース5件 |
| `NoticeSeeder.php` | 重要なお知らせ3件（掲載中・予約・掲載終了を各1件） |
| `BannerSeeder.php` | メインビジュアル3件 |
| `DatabaseSeeder.php` | **修正**: 上記を依存順に呼び出す |

### 設定

| ファイル | 内容 |
|---|---|
| `config/auth.php` | **修正**: `admin` ガード（driver `session`, provider `admins`）と `admins` プロバイダ（`App\Models\Admin`）を追加。単位03で使う |
| `.env.example` | **修正**: `ADMIN_SEED_PASSWORD` のプレースホルダーを追記（Bash の `printf` 追記で行う。Edit ツールは使わない） |

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Database/SchemaTest.php` | 全テーブルが作成され、主要な一意制約・外部キーが機能すること |
| `tests/Feature/Database/OrderSnapshotTest.php` | 注文作成後に商品価格・商品名・カテゴリ名・会員氏名・EC基本設定・送料設定を変更しても、`orders` / `order_items` の値が変わらないこと。商品を削除しても注文明細が残り `product_id` が `null` になること |
| `tests/Unit/Enums/OrderStatusTest.php` | 各ステータスの `label()` が期待する日本語を返すこと |
| `tests/Feature/Database/SeederTest.php` | Seeder 実行後に都道府県47件・送料設定47件・EC基本設定1件が存在すること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。異なる方針を採る場合は実行時に指示すること。

1. **マイグレーションの実行**: `php artisan migrate` は `CLAUDE.md` で自動実行禁止。実装完了後、実行の承認を求める。テストは SQLite in-memory で動くため承認前にテストは実行できる。
2. **閲覧履歴の未ログイン時の扱い**: 推奨=ログイン会員のみ DB に保持し、未ログイン時は TOP の「最近見た商品」セクションを表示しない。（代替: セッションに保持して未ログインでも表示する）
3. **注文キャンセル時の在庫戻し**: 要件で「引き続き検討」。推奨=キャンセル時に在庫を戻す（`stocks.quantity` を明細の数量分加算する）。単位07・17で実装する。
4. **お問い合わせの保存**: 要件で「実装有無を引き続き検討」。推奨=`contacts` テーブルに保存する（メール送信は行わない）。単位18で実装する。
5. **`created_at` / `updated_at` の型**: Laravel 標準の `timestamp` 型を使う（上限 2038-01-19）。これらは書き込み時の現在時刻のみを保持するため当面問題にならないが、2038年より前に `datetime` への移行が必要になる。将来日付を持つ業務上の日付列はすべて `date` 型としており、この制約の影響を受けない。
6. **会員の退会**: 論理削除・物理削除のいずれも実装しない（要件に記載なし）。休会は `users.status = suspended` で表す。
7. **ランキング集計条件**: 要件で「引き続き検討」。推奨=販売数ベース（前月の `order_items.quantity` 合計。キャンセル注文は除外）。単位14で実装する。
8. **注文番号の形式**: `BNC-YYMM-NNNN`（`NNNM` は月内連番の4桁ゼロ埋め）。モックの `VLC-` はブランド統一の決定に従い `BNC-` とする。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/1_system_overview.md`（横断ドキュメント: 技術構成・ブランド表記・2ガード認証前提・共通業務ルール）
  - `docs/2_database.md`（全テーブル定義の正本。以降の各仕様書の `## 使用テーブル` はここへリンクする）
- 本計画ファイル（`docs/plans/01-database-design.md`）を削除し、`docs/reports/implementation-tracker.md` の状態を「完了」に更新する（同一の実装コミットに含める）
