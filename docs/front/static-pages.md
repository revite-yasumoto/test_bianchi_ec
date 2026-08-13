# フロント 買い物ガイド・法的ページ

## 機能概要

- **対象画面・機能の目的:** 購入前に確認する取引条件（送料・支払い方法・発送・返品）と、法令上の表記を掲示する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/guide` | `guide` | 買い物ガイド |
| GET | `/legal/tokushoho` | `legal.tokushoho` | 特定商取引法に基づく表記 |
| GET | `/legal/privacy` | `legal.privacy` | プライバシーポリシー |
| GET | `/legal/terms` | `legal.terms` | 利用規約 |

- **アクセス権限・ミドルウェア:** なし（未ログインで閲覧できる）。
- **本ドキュメントのスコープ:** 上記4ページの表示。送料・代引き手数料・送料無料しきい値の設定は [docs/admin/shipping-setting.md](../admin/shipping-setting.md)・[docs/admin/ec-setting.md](../admin/ec-setting.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `shipping_settings` | 買い物ガイドの都道府県別の送料・お届け日数の表 |
| `prefectures` | 同表の都道府県名 |
| `ec_settings` | 送料無料しきい値・代引き手数料の案内文 |

法的3ページはテーブルを参照しない。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
買い物ガイド                              （h1）

お支払い方法                              （h2）
  銀行振込 / 代金引換（手数料 ¥330）

送料                                      （h2）
  ¥10,000 以上で送料無料
  ┌──────────┬───────────┐
  │ 都道府県  │ 送料 / 日数 │   ← 横幅が足りないときは表だけ横スクロール
  └──────────┴───────────┘

発送とお届け / 返品・交換 / お問い合わせ   （h2）
```

- 4ページとも最大760px・中央寄せの共通枠を使い、`h1` にページ名、各節の見出しを `h2` に置く。
- 特定商取引法に基づく表記は用語と説明の対（`dl`）で組み、PC幅では見出し180pxの2カラム、SP幅では縦積みにする。
- 4ページとも冒頭に、デモサイトであり記載内容が架空である旨を明記する。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/Static/Guide.tsx
type Props = {
    shippingTable: ShippingTableRow[];   // { prefecture_name, fee, delivery_days }
    ecSetting: { free_shipping_threshold: number; cod_fee: number };
};
```

`ShippingTableRow` は [docs/front/product-show.md](product-show.md) が正本（送料モーダルと同じ形）。法的3ページは props を持たない。

### 入力値バリデーションルール

なし（入力を受け取らない）。

### 主要な処理フロー

**買い物ガイド**

1. 送料設定を `prefecture_id` 昇順で取得し、都道府県名・送料・お届け日数の表にする。
2. EC基本設定から送料無料しきい値と代引き手数料を取得し、案内文に埋め込む。
3. 返品・交換の条件はダミーテキストとして画面に直接記述する。

**法的3ページ**

1. 本文をページコンポーネントに直接記述して返す。データベース・管理画面を参照しない。

## 業務ルール

- 法的3ページと返品・交換の条件は要件でダミーと定められている。会社名・所在地・連絡先・代表者名はすべて架空値を使い、実在の企業情報を書かない。
- 4ページとも管理画面からの編集はできない。文面の変更はコードの変更として扱う。
- 送料・支払い方法の案内は設定値から組み立てるため、管理画面での設定変更がそのまま反映される（文面の追随を運用で行わない）。

## 関連ドキュメント

- [docs/admin/shipping-setting.md](../admin/shipping-setting.md) — 都道府県別の送料・お届け日数の設定元
- [docs/admin/ec-setting.md](../admin/ec-setting.md) — 送料無料しきい値・代引き手数料の設定元
- [docs/shipping-calculation.md](../shipping-calculation.md) — 送料・代引き手数料の算出規則の正本
- [docs/front/product-show.md](product-show.md) — 送料モーダル（`ShippingTableRow` の正本。本ページへの導線を持つ）
- [docs/front/contact.md](contact.md) — 買い物ガイドから案内するお問い合わせ
- [docs/front/auth.md](auth.md) — 会員登録の同意チェックから参照する利用規約・プライバシーポリシー
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/StaticPageController.php` |
| Component | `resources/js/front/Components/Support/StaticPageLayout.tsx` |
| Page | `resources/js/front/Pages/Static/Guide.tsx` |
| Page | `resources/js/front/Pages/Static/Tokushoho.tsx` |
| Page | `resources/js/front/Pages/Static/Privacy.tsx` |
| Page | `resources/js/front/Pages/Static/Terms.tsx` |
| Test | `tests/Feature/Front/StaticPageTest.php` |

## 受け入れ条件

- 未ログインで4ページとも表示できる: `tests/Feature/Front/StaticPageTest.php`（`未ログインで買い物ガイドを閲覧できる`・`未ログインで特定商取引法に基づく表記を閲覧できる`・`未ログインでプライバシーポリシーを閲覧できる`・`未ログインで利用規約を閲覧できる`）
- 買い物ガイドに都道府県別の送料表が渡る: 同上（`未ログインで買い物ガイドを閲覧できる`）
- 送料無料しきい値・代引き手数料が設定の現在値を反映する: 同上（`買い物ガイドの送料案内は基本設定の現在値を反映する`）
- 記載内容が架空値であること・レスポンシブ時の表の横スクロール: 自動テストなし。目視確認で担保する
