# 送料設定マスタ

## 機能概要

- **対象画面・機能の目的:** 47都道府県ごとの送料と配送予定日数を一覧編集し、一括で保存する。フロント側の送料計算・配達予定日表示の設定値を管理する画面。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/shipping-settings` | `admin.shipping-settings.index` | `auth:admin` |
| PUT | `/admin/shipping-settings` | `admin.shipping-settings.update` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 送料設定の一覧表示と一括更新。設定値を使った送料・配達予定日の算出は [docs/shipping-calculation.md](../shipping-calculation.md) が正本。

## 使用テーブル

`shipping_settings` を起点に `prefectures` を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

`prefectures` は不変のマスタで、`id` が JIS X 0401 の都道府県コード（1〜47）に一致する。`shipping_settings` は都道府県ごとに1行（`prefecture_id` は一意）。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| 47都道府県ごとに送料と配送予定日数を設定します。      [設定を保存]  |
| 初期値：北海道・沖縄 1,000円／その他 500円。                       |
+------------------------------------------------------------------+
+------------------------------------------------------------------+
| 北海道 [ 1000]円 [ 5]日 | 青森県 [ 500]円 [ 3]日 | 岩手県 ...     |
| 宮城県 [  500]円 [ 3]日 | 秋田県 [ 500]円 [ 3]日 | 山形県 ...     |
|  … 47件（都道府県コードの昇順） …                                 |
+------------------------------------------------------------------+
```

- 都道府県のカードは `repeat(auto-fill, minmax(230px, 1fr))` のグリッドで、画面幅に応じて列数が変わる（PCで4〜5列、狭い幅で1列）。
- 「設定を保存」は確認モーダル（`ConfirmDialog`）を経て送信し、完了後にトースト「設定を保存しました」を表示する。
- バリデーションエラー時は一覧の上に赤枠のメッセージを1件表示し、該当する都道府県のカードを赤枠にする（個別の項目メッセージは表示しない）。
- 都道府県の追加・削除・並べ替えのUIは持たない。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type ShippingSettingRowData = {
    id: number;
    prefecture_id: number;
    prefecture_name: string;
    fee: number;
    delivery_days: number;
};

type ShippingSettingEditorProps = {
    settings: ShippingSettingRowData[];   // 常に47件、都道府県コードの昇順
};
```

送信時は入力値（文字列）を数値へ変換し、`{ settings: { id, fee, delivery_days }[] }` として PUT する。

- **入力値バリデーションルール（`Admin\ShippingSetting\UpdateShippingSettingsRequest`）:**

| 項目 | ルール |
|---|---|
| `settings` | 必須・配列・要素数がちょうど47 |
| `settings.*.id` | 必須・整数・重複不可・`shipping_settings.id` に存在 |
| `settings.*.fee` | 必須・整数・0以上・100,000以下 |
| `settings.*.delivery_days` | 必須・整数・1以上・30以下 |

- **主要な処理フロー:**

**一覧（`index()`）:** `shipping_settings` を `prefecture_id` の昇順で47件取得し、`prefecture` を eager load して都道府県名とともに返す。

**一括更新（`update()`）:**
1. 「設定を保存」→ 確認モーダル（「変更内容はフロント側の送料計算・金額表示に即時反映されます。確定済みの注文の金額は変わりません。」）
2. `BulkUpdateShippingSettings` が1トランザクション内で47件を更新する。
3. 直前の画面へ戻り、トーストを表示する。
4. 1件でもバリデーションに失敗した場合は1件も更新せず、エラーとともに画面へ戻る。

## 業務ルール

- 都道府県は47件固定。追加・削除は行わないため、更新リクエストの要素数もちょうど47件に限る。
- 一括入力補助（全件同額の一括設定等）は設けず、47件を個別に入力する。

## 関連ドキュメント

- [docs/shipping-calculation.md](../shipping-calculation.md) — 本画面の設定値を使った送料・配達予定日・合計金額の算出の正本
- [docs/admin/ec-setting.md](ec-setting.md) — 送料無料しきい値・代引き手数料を管理する画面
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`ConfirmDialog`・トーストの正本
- [docs/2_database.md](../2_database.md) — `shipping_settings`・`prefectures` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/ShippingSettingController.php` |
| FormRequest | `app/Http/Requests/Admin/ShippingSetting/UpdateShippingSettingsRequest.php` |
| Action | `app/Actions/Admin/ShippingSetting/BulkUpdateShippingSettings.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/ShippingSetting/Index.tsx` |
| Component | `resources/js/admin/Components/ShippingSetting/ShippingSettingEditor.tsx` |
| Component | `resources/js/admin/Components/ShippingSetting/ShippingSettingRow.tsx` |
| Test | `tests/Feature/Admin/ShippingSetting/ShippingSettingIndexTest.php` |
| Test | `tests/Feature/Admin/ShippingSetting/ShippingSettingUpdateTest.php` |

## 受け入れ条件

- 47件が都道府県コードの昇順で表示される: `tests/Feature/Admin/ShippingSetting/ShippingSettingIndexTest.php`（`送料設定マスタに47件が都道府県コードの昇順で表示される`）
- 初期値（北海道・沖縄県1,000円、その他500円）が表示される: 同上（`初期値として北海道と沖縄県は1000円その他は500円が表示される`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 47件の送料・配送予定日数を一括更新できる: `tests/Feature/Admin/ShippingSetting/ShippingSettingUpdateTest.php`（`全47件の送料と配送予定日数が一括更新される`）
- 要素数が47件でないリクエストを弾く: 同上（`要素数が47件でないリクエストは弾かれる`）
- 範囲外の送料・配送予定日数、存在しないIDを弾く: 同上（`上限を超える送料は弾かれる`・`範囲外の配送予定日数は弾かれる`・`存在しない送料設定のidは弾かれる`）
- 1件でも不正な値があれば全件更新されない: 同上（`一件でも不正な値があれば他の46件も更新されない`）
- 未認証は更新できない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 確認モーダル・トースト・グリッドのレスポンシブ表示: 自動テストなし。目視確認で担保する
