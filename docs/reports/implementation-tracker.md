# Bianchi EC サイト 実装進捗トラッカー

`/core:implement` の分割モードで作成した実装計画の一覧と進捗。仕様書ではないため、仕様書の同期・監査の対象外（`CLAUDE.md`「同期対象外の記録ディレクトリ」）。

## 参照資料

| 資料 | パス | 位置づけ |
|---|---|---|
| 要件定義書 | `docs/ec_demo_site_requirements.md` | 機能仕様の正典 |
| モック（フロント） | `docs/Markdown file UI mockups.zip` 内 `EC Demo Front.dc.html` | 見た目・挙動・遷移の参照 |
| モック（管理画面） | `docs/Markdown file UI mockups.zip` 内 `EC Demo Admin.dc.html` | 同上 |
| デザイントークン | 同zip内 `_ds/re-vite-.../site.css` | 配色・フォント・角丸の参照 |

## 確定済みの全体方針

| 論点 | 決定 |
|---|---|
| 認証基盤 | 素の Laravel 認証（`Auth` ファサード）で自前実装。会員 `web` ガード／管理者 `admin` ガードの2ガード構成。認証スキャフォールドは導入しない |
| CSV処理 | 素の PHP（`fgetcsv` / `fputcsv`）で実装。CSVライブラリは導入しない |
| ブランド表記 | `Bianchi` に統一（モックHTMLの `VELOCE` は読み替える）。注文番号の接頭辞は `BNC-` |
| 進行モード | 分割（単位ごとに計画ファイルを保存し、後日個別に実行） |

## 単位一覧

依存は上から下への単方向。番号順に実行する。

| No | 単位 | 計画ファイル | 依存 | 状態 |
|---|---|---|---|---|
| 01 | DB設計（全テーブル・Model・Seeder） | 完了（`docs/1_system_overview.md`, `docs/2_database.md`） | - | 完了 |
| 02 | 会員認証＋フロント共通レイアウト | 完了（`docs/front/auth.md`, `docs/front/common-layout.md`） | 01 | 完了 |
| 03 | 管理者認証＋管理画面共通レイアウト | 完了（`docs/admin/auth.md`, `docs/admin/common-layout.md`） | 01 | 完了 |
| 04 | 管理：カテゴリ管理・規格管理 | 完了（`docs/admin/category.md`, `docs/admin/spec-option.md`） | 03 | 完了 |
| 05 | 管理：商品管理（一覧・登録／編集） | 完了（`docs/admin/product-index.md`, `docs/admin/product-form.md`） | 04 | 完了 |
| 06 | 管理：在庫管理 | 完了（`docs/admin/stock.md`） | 05 | 完了 |
| 07 | 管理：注文管理 | 完了（`docs/admin/order-index.md`, `docs/admin/order-show.md`） | 03 | 完了 |
| 08 | 管理：会員マスタ・管理者マスタ | 完了（`docs/admin/member.md`, `docs/admin/admin-user.md`） | 03 | 完了 |
| 09 | 管理：CSVインポート／エクスポート | 完了（`docs/admin/csv.md`） | 05, 08 | 完了 |
| 10 | 管理：送料設定マスタ・EC基本設定 | 完了（`docs/admin/shipping-setting.md`, `docs/admin/ec-setting.md`, `docs/shipping-calculation.md`） | 03 | 完了 |
| 11 | 管理：新着ニュース・重要なお知らせ管理 | 完了（`docs/admin/news.md`, `docs/admin/notice.md`） | 03 | 完了 |
| 12 | 管理：ダッシュボード | 完了（`docs/admin/dashboard.md`） | 07 | 完了 |
| 13 | フロント：商品一覧・商品詳細 | 完了（`docs/front/product-index.md`, `docs/front/product-show.md`, `docs/front/favorite.md`） | 02, 05, 06 | 完了 |
| 14 | フロント：TOPページ | 完了（`docs/front/top.md`, `docs/ranking.md`） | 13, 11 | 完了 |
| 15 | フロント：カート | 完了（`docs/front/cart.md`） | 13, 10 | 完了 |
| 16 | フロント：購入手続き〜注文完了 | 完了（`docs/front/checkout.md`, `docs/front/order-complete.md`, `docs/order-snapshot.md`） | 15, 10 | 完了 |
| 17 | フロント：マイページ | 完了（`docs/front/mypage-order.md`, `docs/front/mypage-address.md`, `docs/front/mypage-profile.md`） | 16 | 完了 |
| 18 | フロント：サポート・法的ページ・お問い合わせ | `docs/plans/18-front-support.md` | 02, 11 | 未着手 |

## 実行方法

```
/core:implement docs/plans/01-database-design.md
```

各単位の完了後、本トラッカーの状態欄を更新する。全単位の完了後に本ファイルを削除する（情報は仕様書と git 履歴に揃うため）。
