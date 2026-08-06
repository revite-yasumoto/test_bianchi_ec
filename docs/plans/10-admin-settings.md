# 単位10: 管理画面 - 送料設定マスタ・EC基本設定

依存: 単位03

フロントの送料・配達予定日・合計金額の算出根拠を管理する2画面。カート（単位15）・購入手続き（単位16）より先に実装する。

## スコープ

- 送料設定マスタ: 47都道府県の送料・配送予定日数を一覧編集して一括保存（確認モーダル付き）
- EC基本設定: 送料無料となる購入金額・代引き手数料・銀行振込の案内文を保存（確認モーダル付き）
- 送料・配達予定日を算出する共通ロジック（フロント側の単位15・16から使う）

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Service） | `laravel-set:laravel` |
| 一括更新・トランザクション | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/shipping-settings` | `admin.shipping-settings.index` | 送料設定マスタ |
| PUT | `/admin/shipping-settings` | `admin.shipping-settings.update` | 47件を一括更新 |
| GET | `/admin/ec-settings` | `admin.ec-settings.edit` | EC基本設定 |
| PUT | `/admin/ec-settings` | `admin.ec-settings.update` | 更新 |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/ShippingSettingController.php` | `index()` / `update()` |
| Controller | `app/Http/Controllers/Admin/EcSettingController.php` | `edit()` / `update()` |
| FormRequest | `app/Http/Requests/Admin/ShippingSetting/UpdateShippingSettingsRequest.php` | 47件の配列バリデーション |
| FormRequest | `app/Http/Requests/Admin/EcSetting/UpdateEcSettingRequest.php` | 3項目のバリデーション |
| Service | `app/Services/Shipping/ShippingCalculator.php` | 都道府県と商品合計から送料・配達予定日を算出（フロント側から使う共通ロジック） |
| Repository | `app/Services/Setting/EcSettingProvider.php` | `ec_settings` の単一行を取得する。リクエスト内でキャッシュする |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/ShippingSetting/Index.tsx` | 説明＋保存ボタンの上部バー＋47都道府県のグリッド（都道府県名・送料input・日数input） |
| Page | `resources/js/admin/Pages/EcSetting/Edit.tsx` | 金額設定カード（送料無料しきい値・代引き手数料）＋銀行振込案内文カード＋保存ボタン |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「送料設定マスタ」「EC基本設定」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
// ShippingSetting/Index.tsx
type ShippingSettingRow = {
  id: number;
  prefecture_id: number;
  prefecture_name: string;
  fee: number;
  delivery_days: number;
};
type Props = { settings: ShippingSettingRow[] };   // 常に47件

// EcSetting/Edit.tsx
type Props = {
  setting: {
    free_shipping_threshold: number;
    cod_fee: number;
    bank_transfer_note: string;
  };
};
```

### バリデーション

`UpdateShippingSettingsRequest`:

| 項目 | ルール |
|---|---|
| `settings` | 必須・配列・**要素数がちょうど47** |
| `settings.*.id` | 必須・`shipping_settings.id` に存在 |
| `settings.*.fee` | 必須・整数・0以上・100,000以下 |
| `settings.*.delivery_days` | 必須・整数・1以上・30以下 |

`UpdateEcSettingRequest`:

| 項目 | ルール |
|---|---|
| `free_shipping_threshold` | 必須・整数・0以上・1,000,000以下 |
| `cod_fee` | 必須・整数・0以上・10,000以下 |
| `bank_transfer_note` | 必須・文字列・最大2000 |

### 主要な処理フロー

**送料設定マスタの表示**: `shipping_settings` を `prefecture_id` の昇順（JIS X 0401 順）で47件取得し、`prefecture` を eager load する。

**送料設定マスタの一括更新**:
1. 「設定を保存」ボタン → 確認モーダル（「変更内容はフロント側の送料計算・金額表示に即時反映されます。」）
2. 1トランザクション内で47件を更新する
3. 「設定を保存しました」をフラッシュ

**EC基本設定の更新**: 確認モーダル → `ec_settings` の `id = 1` を更新 → 「設定を保存しました」をフラッシュ

### 送料・配達予定日の算出ロジック（`ShippingCalculator`）

以降の単位15・16が使う共通ロジック。本単位で実装し、その正本を本単位の仕様書に置く。

```php
// 入力: 都道府県ID、商品合計（税込）、支払方法
// 出力: 素の送料、適用送料、代引き手数料、配送日数、配達予定日、合計
```

| 出力項目 | 算出方法 |
|---|---|
| 素の送料 `fee_base` | `shipping_settings.fee`（該当都道府県） |
| 適用送料 `fee` | 商品合計 >= `ec_settings.free_shipping_threshold` なら `0`、それ未満なら `fee_base` |
| 代引き手数料 `cod_fee` | 支払方法が代引きなら `ec_settings.cod_fee`、銀行振込なら `0` |
| 配送日数 `delivery_days` | `shipping_settings.delivery_days`（該当都道府県） |
| 配達予定日 `estimated_delivery_date` | 当日日付 + `delivery_days` 日 |
| 合計 `total` | 商品合計 + 適用送料 + 代引き手数料 |

配達予定日は**具体的な日付**（例 `8月8日（土）`）で表示する。営業日・休業日の考慮は行わない（暦日で加算する）。

## 業務ルール

- 送料無料しきい値の判定は**商品合計（税込）のみ**で行う。送料・代引き手数料は判定に含めない
- 送料無料が適用された場合、送料は `0` として表示する（「無料」と表記する）
- 配達予定日は暦日で加算する。土日祝・年末年始の休業は考慮しない
- 設定の変更は以降の新規注文にのみ影響する。確定済みの注文は注文時点の値をスナップショットとして保持しているため変わらない（単位01・16）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/ShippingSetting/ShippingSettingIndexTest.php` | 47件が JIS X 0401 順で表示されること／初期値（北海道1,000円・沖縄県1,000円・その他500円）が正しいこと |
| `tests/Feature/Admin/ShippingSetting/ShippingSettingUpdateTest.php` | 47件が一括更新されること／要素数が47でないリクエストを弾くこと／範囲外の値を弾くこと／1件でも失敗すれば全件ロールバックされること |
| `tests/Feature/Admin/EcSetting/EcSettingUpdateTest.php` | 3項目が更新されること／範囲外の値を弾くこと／案内文の最大長超過を弾くこと |
| `tests/Unit/Services/Shipping/ShippingCalculatorTest.php` | しきい値未満で都道府県の送料が適用されること／しきい値以上で送料0になること／しきい値と同額のとき送料0になること（境界値）／代引き手数料が支払方法で切り替わること／配達予定日が当日+配送日数になること／合計金額の算出 |
| `tests/Feature/Admin/EcSetting/SettingSnapshotTest.php` | 設定を変更しても確定済み注文の金額表示が変わらないこと（単位16の実装後に有効化する） |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **送料無料の判定基準**: 要件は「10,000円以上で送料無料」。推奨=**商品合計（税込）のみ**で判定し、送料・代引き手数料は含めない。しきい値と同額のときは無料とする。
2. **配達予定日の営業日考慮**: 要件は「本日日付＋都道府県ごとの最大配送日数」。推奨=**暦日で加算する**（土日祝を考慮しない）。
3. **都道府県の追加・削除**: 47件固定。追加・削除のUIは実装しない。
4. **送料設定の一括入力補助**: 要件に記載なし。実装しない（47件を個別に入力する）。
5. **税率・税計算**: 価格はすべて税込で保持し、税額の内訳は表示しない（要件に税表示の指定がないため）。
6. **銀行振込の案内文の表示先**: 要件は「注文完了メール・注文完了画面に表示」。メール送信は行わないため、推奨=**注文完了画面と注文詳細（マイページ）に表示する**。
7. **キャッシュ**: `ec_settings` は全ページで参照されるため、推奨=リクエスト内メモ化のみ（アプリケーションキャッシュは使わない。設定変更の即時反映を優先する）。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/shipping-setting.md`
  - `docs/admin/ec-setting.md`
  - `docs/shipping-calculation.md`（送料・配達予定日・合計金額の算出ロジックの**正本**。横断ドキュメント。単位15・16・07の仕様書からリンクする）
- 本計画ファイルを削除し、トラッカーの状態を更新する
