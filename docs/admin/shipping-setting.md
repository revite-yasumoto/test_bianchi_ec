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
| 北海道                                                            |
| 北海道 [ 1000]円 [ 5]日                                           |
|                                                                   |
| 東北                                                              |
| 青森県 [ 500]円 [ 3]日 | 岩手県 [ 500]円 [ 3]日 | 宮城県 ...      |
|                                                                   |
| 関東                                                              |
| 茨城県 [ 500]円 [ 2]日 | 栃木県 [ 500]円 [ 2]日 | 群馬県 ...      |
|  … 8地方・計47件（都道府県コードの昇順） …                        |
+------------------------------------------------------------------+
```

- 都道府県は8つの地方（北海道／東北／関東／中部／近畿／中国／四国／九州・沖縄）に分け、地方名の見出しを挟んで並べる。47件がフラットに並ぶと目的の県を見つけにくいため。
- 見出しの中では都道府県コードの昇順を保つ。地方の並び順もコードの昇順に従う。
- 都道府県のカードは `repeat(auto-fill, minmax(230px, 1fr))` のグリッドで、画面幅に応じて列数が変わる（PCで4〜5列、狭い幅で1列）。
- 送料・日数の入力欄は数値のスピナーを表示しない。入力欄が狭く、右寄せした数値にスピナーが重なって読めなくなるため。増減はキーボードの上下キーで行える。
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
    region: string;          // 所属する地方。App\Enums\Region の値
    fee: number;
    delivery_days: number;
};

type ShippingSettingEditorProps = {
    settings: ShippingSettingRowData[];              // 常に47件、都道府県コードの昇順
    regions: { value: string; label: string }[];     // 見出しの並び順（8件）
};
```

地方の区分は `App\Enums\Region` が単一情報源で、所属は都道府県コードから判定する（`prefectures` に地方の列は持たない。行政区分として固定で、運用で変える対象ではないため）。

**グループ化は表示だけで、フォームは47件のまま保つ。** 一括更新が47件ちょうどを要求するため、地方ごとに分けた配列を送信すると保存できなくなる。

送信時は入力値（文字列）を数値へ変換し、`{ settings: { id, fee, delivery_days }[] }` として PUT する。

- **入力値バリデーションルール（`Admin\ShippingSetting\UpdateShippingSettingsRequest`）:**

| 項目 | ルール |
|---|---|
| `settings` | 必須・配列・要素数がちょうど47 |
| `settings.*.id` | 必須・整数・重複不可・`shipping_settings.id` に存在 |
| `settings.*.fee` | 必須・整数・0以上・100,000以下 |
| `settings.*.delivery_days` | 必須・整数・1以上・30以下 |

- **主要な処理フロー:**

**一覧（`index()`）:** `shipping_settings` を `prefecture_id` の昇順で47件取得し、`prefecture` を eager load して都道府県名とともに返す。各行には所属する地方（`Region::of()` が都道府県コードから判定）を添え、見出しの並び順として地方の一覧も渡す。

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
| Enum | `app/Enums/Region.php` |
| Controller | `app/Http/Controllers/Admin/ShippingSettingController.php` |
| FormRequest | `app/Http/Requests/Admin/ShippingSetting/UpdateShippingSettingsRequest.php` |
| Action | `app/Actions/Admin/ShippingSetting/BulkUpdateShippingSettings.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/ShippingSetting/Index.tsx` |
| Component | `resources/js/admin/Components/ShippingSetting/ShippingSettingEditor.tsx` |
| Component | `resources/js/admin/Components/ShippingSetting/ShippingSettingRow.tsx` |
| Test | `tests/Unit/Enums/RegionTest.php` |
| Test | `tests/Feature/Admin/ShippingSetting/ShippingSettingIndexTest.php` |
| Test | `tests/Feature/Admin/ShippingSetting/ShippingSettingUpdateTest.php` |

## 受け入れ条件

- 47件が都道府県コードの昇順で表示される: `tests/Feature/Admin/ShippingSetting/ShippingSettingIndexTest.php`（`送料設定マスタに47件が都道府県コードの昇順で表示される`）
- 各行に所属する地方が付き、見出し用の地方が8件渡される: 同上（`各行に所属する地方が付与される`・`地方の選択肢が八件渡される`）
- 都道府県コードから地方が正しく判定される: `tests/Unit/Enums/RegionTest.php`（`都道府県コードから所属する地方が決まる`。各地方の最初と最後のコードで検証）
- 47件がいずれかの地方に属し、見出しが8件になる: 同上（`全ての都道府県がいずれかの地方に属する`・`見出しの選択肢は八件で都道府県コードの順に並ぶ`）
- 地方ごとに見出しが挟まれた表示: 自動テストなし。目視確認で担保する
- 初期値（北海道・沖縄県1,000円、その他500円）が表示される: 同上（`初期値として北海道と沖縄県は1000円その他は500円が表示される`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 47件の送料・配送予定日数を一括更新できる: `tests/Feature/Admin/ShippingSetting/ShippingSettingUpdateTest.php`（`全47件の送料と配送予定日数が一括更新される`）
- 要素数が47件でないリクエストを弾く: 同上（`要素数が47件でないリクエストは弾かれる`）
- 範囲外の送料・配送予定日数、存在しないIDを弾く: 同上（`上限を超える送料は弾かれる`・`範囲外の配送予定日数は弾かれる`・`存在しない送料設定のidは弾かれる`）
- 1件でも不正な値があれば全件更新されない: 同上（`一件でも不正な値があれば他の46件も更新されない`）
- 未認証は更新できない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 確認モーダル・トースト・グリッドのレスポンシブ表示: 自動テストなし。目視確認で担保する
