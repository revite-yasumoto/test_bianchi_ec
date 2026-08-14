# EC基本設定

## 機能概要

- **対象画面・機能の目的:** 送料無料となる購入金額・代引き手数料・銀行振込の案内文を編集する。EC全体に効く単一の設定を管理する画面。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/ec-settings` | `admin.ec-settings.edit` | `auth:admin` |
| PUT | `/admin/ec-settings` | `admin.ec-settings.update` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** EC基本設定の表示と更新。設定値を使った送料・合計金額の算出は [docs/shipping-calculation.md](../shipping-calculation.md) が正本。

## 使用テーブル

`ec_settings`（単一行、`id = 1`）。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+--------------------------------------------------+
| 金額設定                                          |
| 送料無料となる購入金額     代引き手数料            |
| [      10000     ]        [      330      ]      |
| 商品合計（税込）がこの…    初期値 330円            |
+--------------------------------------------------+
+--------------------------------------------------+
| 銀行振込の案内文                                   |
| 銀行振込を選んだ注文の完了画面と注文詳細に表示されます。|
| +----------------------------------------------+ |
| |                                              | |
| |                                    （5行）    | |
| +----------------------------------------------+ |
+--------------------------------------------------+
[設定を保存]
```

- フォーム全体の最大幅は640px。金額設定の2項目は `repeat(auto-fit, minmax(200px, 1fr))` のグリッドで、狭い幅では縦積みになる。
- 「設定を保存」は確認モーダル（`ConfirmDialog`）を経て送信し、完了後にトースト「設定を保存しました」を表示する。
- バリデーションエラーは各項目の下に表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type EcSettingFormProps = {
    setting: {
        free_shipping_threshold: number;
        cod_fee: number;
        bank_transfer_note: string;
    };
};
```

送信時は金額の入力値（文字列）を数値へ変換して PUT する。

- **入力値バリデーションルール（`Admin\EcSetting\UpdateEcSettingRequest`）:**

| 項目 | ルール |
|---|---|
| `free_shipping_threshold` | 必須・整数・0以上・1,000,000以下 |
| `cod_fee` | 必須・整数・0以上・10,000以下 |
| `bank_transfer_note` | 必須・文字列・2,000文字以下 |

- **主要な処理フロー:**

**表示（`edit()`）:** `EcSettingProvider` が `ec_settings` の単一行（`id = 1`）を取得し、3項目を返す。

**更新（`update()`）:**
1. 「設定を保存」→ 確認モーダル（「変更内容はフロント側の送料計算・金額表示に即時反映されます。確定済みの注文の金額は変わりません。」）
2. `ec_settings` の `id = 1` を更新する。
3. 直前の画面へ戻り、トーストを表示する。

## 業務ルール

- `ec_settings` は単一行のみを持ち、レコードの追加・削除は行わない（初期行は `EcSettingSeeder` が投入する）。
- 銀行振込の案内文は、銀行振込を選んだ注文の注文完了画面と注文詳細（マイページ）に表示する。注文完了メールの送信は本プロジェクトの対象外。

## 関連ドキュメント

- [docs/shipping-calculation.md](../shipping-calculation.md) — 本画面の設定値を使った送料・合計金額の算出の正本
- [docs/admin/shipping-setting.md](shipping-setting.md) — 都道府県ごとの送料・配送予定日数を管理する画面
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`ConfirmDialog`・トーストの正本
- [docs/2_database.md](../2_database.md) — `ec_settings` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/EcSettingController.php` |
| FormRequest | `app/Http/Requests/Admin/EcSetting/UpdateEcSettingRequest.php` |
| Service | `app/Services/Setting/EcSettingProvider.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/EcSetting/Edit.tsx` |
| Component | `resources/js/admin/Components/EcSetting/EcSettingForm.tsx` |
| Test | `tests/Feature/Admin/EcSetting/EcSettingEditTest.php` |
| Test | `tests/Feature/Admin/EcSetting/EcSettingUpdateTest.php` |

## 受け入れ条件

- 保存済みの3項目が表示される: `tests/Feature/Admin/EcSetting/EcSettingEditTest.php`（`保存済みの基本設定が表示される`）
- 未認証は画面を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 3項目を更新できる: `tests/Feature/Admin/EcSetting/EcSettingUpdateTest.php`（`送料無料しきい値と代引き手数料と案内文が更新される`）
- 範囲外の金額を弾く: 同上（`上限を超える送料無料しきい値は弾かれる`・`負の代引き手数料は弾かれる`）
- 案内文の未入力・最大長超過を弾く: 同上（`案内文は必須`・`案内文が2000文字を超えると弾かれる`）
- 未認証は更新できない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 確認モーダル・トースト・金額設定のレスポンシブ表示: 自動テストなし。目視確認で担保する
