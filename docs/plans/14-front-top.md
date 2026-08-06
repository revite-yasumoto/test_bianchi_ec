# 単位14: フロント - TOPページ

依存: 単位13, 単位11

要件で採用が決まっている「案C」のTOPページ。7つのセクションで構成する。

## スコープ

- 重要なお知らせのリンク表示（最上部・表示面積を抑える）
- メインビジュアル（複数バナーの自動スライド切替・ドットインジケータ）
- 購入方法から探す（カテゴリ入口カード）
- ランキング（全体／カテゴリ別のタブ切替）
- おすすめ
- 最近見た商品（閲覧履歴）
- 新着ニュース（最新4件＋「すべて見る」）
- ランキング集計のコンソールコマンドとスケジュール登録

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / Service / Console Command） | `laravel-set:laravel` |
| 集計クエリ（`GROUP BY` を含む） | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php`）

| メソッド | パス | ルート名 | 認証 | 内容 |
|---|---|---|---|---|
| GET | `/` | `top` | 不要 | TOPページ |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/TopController.php` | `index()` |
| Service | `app/Services/Front/Top/TopPageService.php` | 7セクション分のデータを組み立て |
| Service | `app/Services/Ranking/RankingAggregator.php` | 前月実績から全体・カテゴリ別ランキングを集計して `product_rankings` に保存 |
| Console | `app/Console/Commands/AggregateProductRankings.php` | `rankings:aggregate` コマンド |
| スケジュール | `routes/console.php` | **修正**: 毎月1日 1:00 に `rankings:aggregate` を登録 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/front/Pages/Top/Index.tsx` | 7セクションを縦に並べる |
| Component | `resources/js/front/Components/Top/NoticeBar.tsx` | 「重要」バッジ＋タイトル＋「詳細 →」の横1行リンク |
| Component | `resources/js/front/Components/Top/HeroSlider.tsx` | 自動スライド（5.2秒間隔）＋ドットインジケータ。PC 21:9 / SP 4:5 |
| Component | `resources/js/front/Components/Top/CategoryEntries.tsx` | カテゴリ入口カード（画像帯＋カテゴリ名＋件数） |
| Component | `resources/js/front/Components/Top/RankingSection.tsx` | タブ切替＋順位バッジ付き商品カード |
| Component | `resources/js/front/Components/Top/RecommendSection.tsx` | おすすめ商品カードグリッド |
| Component | `resources/js/front/Components/Top/HistorySection.tsx` | 横スクロールの「最近見た商品」 |
| Component | `resources/js/front/Components/Top/NewsSection.tsx` | 日付＋種別バッジ＋タイトルの行リスト＋「すべて見る →」 |
| Hook | `resources/js/front/hooks/useAutoSlide.ts` | 自動スライドのタイマー管理（アンマウント時にクリア） |

## インターフェース ＆ データロジック

### Props の型

```ts
type Props = {
  notice: { id: number; title: string } | null;      // 掲載中のうち最新1件
  banners: { tag: string; title: string; subtitle: string | null; background: string; link_url: string | null }[];
  categoryEntries: { id: number; name: string; product_count: number; background: string }[];
  rankingTabs: { key: string; label: string; category_id: number | null }[];
  rankings: Record<string, (ProductCardData & { rank_position: number })[]>;   // タブキー → 商品配列
  rankingUpdatedAt: string | null;                   // 'YYYY.MM.DD HH:mm'
  recommends: ProductCardData[];
  histories: ProductCardData[];                      // ログイン中のみ。未ログインは空配列
  news: { id: number; published_on: string; category: string; title: string }[];   // 最新4件
};
```

`ProductCardData` は単位13で定義した型を再利用する。

### 各セクションのデータ取得

| セクション | 取得方法 |
|---|---|
| 重要なお知らせ | `Notice::displayable()` のうち `display_start_on` の降順で1件。0件ならセクション非表示 |
| メインビジュアル | `Banner::active()` を `sort_order` 昇順で取得。0件ならセクション非表示 |
| 購入方法から探す | `categories` を `sort_order` 昇順で取得し、公開商品の件数を付与 |
| ランキング | `product_rankings` の最新の `target_year_month` について、`category_id = null`（全体）と各カテゴリ別を `rank_position` 昇順で上位4件ずつ取得。商品が非公開・削除済みなら除外 |
| おすすめ | 公開商品のうち `id` の降順で4件（新着をおすすめとして扱う） |
| 最近見た商品 | ログイン中のみ `browsing_histories` を `viewed_at` 降順で6件。商品が非公開なら除外 |
| 新着ニュース | `News::published()` を `published_on` 降順で4件 |

### ランキング集計（`RankingAggregator`）

要件: 集計は毎月1日 1:00、公開は毎月1日 7:00。

1. 集計対象月を「前月」とする（例: 2026-08-01 に実行 → `target_year_month = '2026-07'`）
2. 前月の `orders`（`status != cancelled`）に紐づく `order_items` を対象に、`product_id` ごとの `quantity` 合計を集計する
3. **`order_items.product_id` が `null` の行（商品が削除済み）は集計から除外する**
4. 全体ランキング（`category_id = null`）と、カテゴリごとのランキングを作る
   - カテゴリの判定は `order_items.category_name`（スナップショット）ではなく、現在の `products.category_id` を使う（現在のカテゴリ構成でタブを出すため）
5. 各ランキングの上位10件を `product_rankings` に保存する（`rank_position` は1から連番）
6. 同一の `target_year_month` のレコードが既にあれば削除して入れ替える（冪等にする）
7. `aggregated_at` に実行日時を設定する

**公開タイミングの制御**: 集計は1:00 に行うが、フロントへの反映は7:00 とする。TOP側の取得条件に `aggregated_at <= 当日7:00 の基準時刻` を含めず、代わりに**当月1日 7:00 を過ぎていなければ前々月分のランキングを表示する**方式とする。具体的には、`target_year_month` の候補のうち「公開開始日時（`target_year_month` の翌月1日 7:00）が現在時刻以前」である最新のものを選ぶ。

### 集計クエリの実装方針（`db-mysql:mysql` 適用結果）

- `selectRaw('order_items.product_id, SUM(order_items.quantity) as total_quantity')` + `groupBy('order_items.product_id')` とし、**`GROUP BY` に無い非集約列を `SELECT` / `ORDER BY` に置かない**（`ONLY_FULL_GROUP_BY`）
- 順位列は `rank_position`（`rank` は MySQL 8.0 の予約語）
- 順位付けはウィンドウ関数を使わず PHP 側で行う（MySQL 5.7 でも動くようにする）
- `product_id` は整数列なので比較値も整数で渡す

## 業務ルール

- メインビジュアルは自動スライドで切り替える。切替間隔は5.2秒。ドットのクリックで任意のスライドへ移動できる
- 重要なお知らせは表示面積を抑え、TOPの最上部にリンク形式の1行で表示する。掲載中のものが0件ならセクションを表示しない
- ランキングは毎月1日 7:00 に前月実績ベースへ切り替わる。集計自体は毎月1日 1:00 に行う
- 「最近見た商品」はログイン中の会員のみに表示する。未ログイン時はセクションを表示しない
- ランキング・おすすめ・閲覧履歴のいずれも、非公開になった商品・削除された商品は表示しない

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/Top/TopPageTest.php` | 未ログインでも表示できること／未ログイン時に `histories` が空配列になること／ログイン時に閲覧履歴が返ること／N+1が発生しないこと |
| `tests/Feature/Front/Top/TopNoticeTest.php` | 掲載中のお知らせのうち最新1件が返ること／掲載期間外のお知らせが返らないこと／0件のとき `null` になること |
| `tests/Feature/Front/Top/TopRankingTest.php` | 全体タブとカテゴリ別タブが返ること／上位4件で切られること／非公開商品が除外されること／ランキングデータがないとき空配列になること |
| `tests/Feature/Front/Top/TopNewsTest.php` | 公開ニュースのみ最新4件が返ること／非公開ニュースが返らないこと |
| `tests/Feature/Ranking/RankingAggregatorTest.php` | 前月の販売数で順位が付くこと／キャンセル注文が除外されること／`product_id` が `null` の明細が除外されること／全体とカテゴリ別の両方が作られること／再実行しても行が重複しないこと（冪等性）／`Carbon::setTestNow()` で対象月が正しく決まること |
| `tests/Feature/Ranking/RankingPublishTimingTest.php` | 当月1日 6:59 では前々月分が表示されること／7:00 以降は前月分が表示されること（境界値） |
| `tests/Feature/Console/AggregateProductRankingsCommandTest.php` | コマンドが正常終了し `product_rankings` が作られること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **ランキング集計条件**: 要件で「引き続き検討（販売数ベース／売上金額ベース）」。推奨=**販売数ベース**（前月の `order_items.quantity` 合計。キャンセル注文を除外）。
2. **ランキングのカテゴリタブ**: モックは「全体ランキング／ロードバイク／パーツ／アパレル」の4タブ固定。推奨=**全体＋登録済みカテゴリのうちランキングデータが存在するカテゴリを動的に出す**（最大4タブ）。
3. **おすすめの選定ロジック**: 要件は「横断的なおすすめ商品」とだけ記載。推奨=**公開商品の新着4件**（管理UIを作らない）。管理者が手動で選べるようにする場合は別単位で `is_recommended` カラムと管理UIを追加する。
4. **メインビジュアルの管理UI**: 要件の管理画面10メニューに含まれないため実装しない。`banners` は Seeder で投入する。
5. **バナー画像**: モックはグラデーション（CSS の `linear-gradient`）で表現している。推奨=**`banners.background` にグラデーション値を保持する**（画像アップロードは実装しない）。
6. **ランキングの公開タイミング制御**: 要件は「毎月1日 朝7時更新（集計は毎月1日 1時）」。推奨=**公開開始日時が現在時刻以前である最新の集計月を選ぶ**方式（上記）。
7. **スケジューラの稼働**: `rankings:aggregate` はスケジュール登録するが、実際の定期実行にはサーバー側の cron 設定（`php artisan schedule:run`）が必要。実装完了報告で案内する。
8. **「購入方法から探す」の件数表示**: モックは「5モデル」等のハードコード。推奨=**公開商品の実件数を表示する**。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/front/top.md`（7セクションのデータ取得条件の**正本**）
  - `docs/ranking.md`（ランキング集計・公開タイミングの**正本**。横断ドキュメント）
- 本計画ファイルを削除し、トラッカーの状態を更新する
