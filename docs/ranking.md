# 商品ランキングの集計と公開

本書は商品ランキングの**集計ロジックと公開タイミングの正本**。TOPページの表示仕様は [docs/front/top.md](front/top.md) が扱う。

## 概要

- 前月の販売実績（販売数ベース）から、全体ランキングとカテゴリ別ランキングを作る。
- 集計は毎月1日 1:00 のスケジュール実行。フロントへの反映は同日 7:00。
- 集計結果は `product_rankings` に保存する。テーブル定義は [docs/2_database.md](2_database.md) が正本。

## 集計（`rankings:aggregate`）

| 項目 | 内容 |
|---|---|
| コマンド | `php artisan rankings:aggregate` |
| スケジュール | 毎月1日 1:00（`routes/console.php`） |
| 集計対象月 | 基準日の前月（例: 2026-08-01 が基準 → `target_year_month = '2026-07'`）。基準日を省略すると当日を使う |
| 保存件数 | 全体・カテゴリ別ごとに上位10件 |

コマンド・スケジュールのほかに、`DatabaseSeeder` が初期データの投入後にも実行する（[docs/2_database.md](2_database.md)）。こちらは投入した注文の月を集計するため基準日を明示的に渡す。

### 集計の条件

1. 対象月の `orders.ordered_at` に含まれる注文を対象とし、**キャンセル済み（`status = cancelled`）は除外**する。
2. `order_items.product_id` が `null` の明細（商品が削除済み）は集計しない。
3. 商品ごとに `order_items.quantity` を合計し、降順に順位を付ける。同数の場合は商品IDの昇順で安定させる。
4. カテゴリの判定は明細のスナップショット（`order_items.category_name`）ではなく、**現在の `products.category_id`** を使う。現在のカテゴリ構成でタブを出すため。
5. 全体ランキングは `category_id = null`、カテゴリ別は各 `category_id` で保存する。`rank_position` は1からの連番。
6. 同一の `target_year_month` の行は削除してから入れ替える（再実行しても重複しない）。
7. `aggregated_at` に実行日時を入れる。

### クエリ上の制約

- 集計は `SUM(order_items.quantity)` と `GROUP BY order_items.product_id, products.category_id` で行い、`GROUP BY` に無い非集約列を `SELECT` に置かない（MySQL の `ONLY_FULL_GROUP_BY`）。
- 順位付けはウィンドウ関数を使わずPHP側で行う。
- 順位の列名は `rank_position`（`rank` は MySQL 8.0 の予約語）。

## 公開タイミング

集計は1:00に走るが、フロントへの反映は7:00とする。判定は**取得側**（`TopPageService`）で行い、集計結果そのものは保持したままにする。

- 集計対象月 `YYYY-MM` の公開開始日時は **`YYYY-MM` の翌月1日 7:00**。
- TOPページは、公開開始日時が現在時刻以前である `target_year_month` のうち最新のものを表示する。
- 該当する集計月が無ければランキングのセクション自体を表示しない。

| 現在時刻 | 表示される集計月 |
|---|---|
| 2026-08-01 06:59 | 2026-06（前々月） |
| 2026-08-01 07:00 | 2026-07（前月） |

## 定期実行の前提

スケジュール登録はアプリ側で済んでいるが、実際の定期実行にはサーバー側で `php artisan schedule:run` を毎分起動する cron 設定が必要。

## 関連ドキュメント

- [docs/front/top.md](front/top.md) — ランキングを表示するTOPページ
- [docs/2_database.md](2_database.md) — `product_rankings`・`orders`・`order_items` のテーブル定義の正本
- [docs/admin/order-index.md](admin/order-index.md) — 注文ステータス（キャンセルの扱い）

## ソースファイル

| 種別 | パス |
|---|---|
| Service | `app/Services/Ranking/RankingAggregator.php` |
| Console | `app/Console/Commands/AggregateProductRankings.php` |
| スケジュール | `routes/console.php` |
| Test | `tests/Feature/Ranking/RankingAggregatorTest.php` |
| Test | `tests/Feature/Ranking/RankingPublishTimingTest.php` |
| Test | `tests/Feature/Console/AggregateProductRankingsCommandTest.php` |

## 受け入れ条件

- 前月の販売数の多い順に順位が付く: `tests/Feature/Ranking/RankingAggregatorTest.php`（`前月の販売数の多い順に順位が付く`）
- キャンセル注文・対象月外の注文・削除済み商品の明細が除外される: 同上（`キャンセル注文は集計から除外される`・`対象月の外の注文は集計されない`・`削除済み商品の明細は集計から除外される`）
- 全体とカテゴリ別の両方が作られる: 同上（`全体とカテゴリ別の両方が作られる`）
- 再実行しても行が重複しない: 同上（`再実行しても行が重複しない`）
- 保存は上位10件まで: 同上（`保存されるのは上位十件までとする`）
- 基準日から集計対象月が決まる: 同上（`基準日から集計対象月が決まる`）
- 公開時刻の境界（6:59は前々月・7:00で前月へ切り替わる）: `tests/Feature/Ranking/RankingPublishTimingTest.php`
- コマンドが正常終了しランキングが作られる: `tests/Feature/Console/AggregateProductRankingsCommandTest.php`
- cron による定期実行: 自動テストなし。サーバー設定として確認する
