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
| 01 | DB設計（全テーブル・Model・Seeder） | `docs/plans/01-database-design.md` | - | 未着手 |
| 02 | 会員認証＋フロント共通レイアウト | `docs/plans/02-auth-front.md` | 01 | 未着手 |
| 03 | 管理者認証＋管理画面共通レイアウト | `docs/plans/03-auth-admin.md` | 01 | 未着手 |
| 04 | 管理：カテゴリ管理・規格管理 | `docs/plans/04-admin-category-spec.md` | 03 | 未着手 |
| 05 | 管理：商品管理（一覧・登録／編集） | `docs/plans/05-admin-product.md` | 04 | 未着手 |
| 06 | 管理：在庫管理 | `docs/plans/06-admin-stock.md` | 05 | 未着手 |
| 07 | 管理：注文管理 | `docs/plans/07-admin-order.md` | 03 | 未着手 |
| 08 | 管理：会員マスタ・管理者マスタ | `docs/plans/08-admin-member-admin.md` | 03 | 未着手 |
| 09 | 管理：CSVインポート／エクスポート | `docs/plans/09-admin-csv.md` | 05, 08 | 未着手 |
| 10 | 管理：送料設定マスタ・EC基本設定 | `docs/plans/10-admin-settings.md` | 03 | 未着手 |
| 11 | 管理：新着ニュース・重要なお知らせ管理 | `docs/plans/11-admin-news-notice.md` | 03 | 未着手 |
| 12 | 管理：ダッシュボード | `docs/plans/12-admin-dashboard.md` | 07 | 未着手 |
| 13 | フロント：商品一覧・商品詳細 | `docs/plans/13-front-product.md` | 02, 05, 06 | 未着手 |
| 14 | フロント：TOPページ | `docs/plans/14-front-top.md` | 13, 11 | 未着手 |
| 15 | フロント：カート | `docs/plans/15-front-cart.md` | 13, 10 | 未着手 |
| 16 | フロント：購入手続き〜注文完了 | `docs/plans/16-front-checkout.md` | 15, 10 | 未着手 |
| 17 | フロント：マイページ | `docs/plans/17-front-mypage.md` | 16 | 未着手 |
| 18 | フロント：サポート・法的ページ・お問い合わせ | `docs/plans/18-front-support.md` | 02, 11 | 未着手 |

## 実行方法

```
/core:implement docs/plans/01-database-design.md
```

各単位の完了後、本トラッカーの状態欄を更新する。全単位の完了後に本ファイルを削除する（情報は仕様書と git 履歴に揃うため）。
