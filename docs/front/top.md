# フロント TOPページ

本書はTOPページの**7セクションのデータ取得条件の正本**。

## 機能概要

- **対象画面・機能の目的:** サイトの入口として、重要なお知らせ・メインビジュアル・カテゴリ入口・ランキング・おすすめ・閲覧履歴・新着ニュースを1画面に集約する。
- **URL / メソッド:** `GET /`（ルート名 `top`）
- **アクセス権限・ミドルウェア:** なし（未ログインでも閲覧できる）。ログイン後の遷移先も本画面。
- **本ドキュメントのスコープ:** TOPページの表示と各セクションのデータ取得条件。ランキングの集計・公開タイミングは [docs/ranking.md](../ranking.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `notices` | 重要なお知らせ（掲載中の最新1件） |
| `banners` | メインビジュアル（管理UIは要件対象外。Seederで投入する） |
| `categories` | カテゴリ入口カードと公開商品の件数 |
| `product_rankings` | ランキング |
| `products` / `product_images` / `product_variants` / `stocks` | 商品カード（在庫の二値表示を含む） |
| `browsing_histories` | 最近見た商品（ログイン中のみ） |
| `news` | 新着ニュース（公開分の最新4件） |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+------------------------------------------------------------------+
| [重要] 台風の影響による配送遅延について              詳細 →        |  ← 掲載中のみ
+------------------------------------------------------------------+
|  2026 ROAD COLLECTION                                             |
|  軽さは、                                    （PC 21:9 / SP 4:5） |
|  遠くまで行くための道具だ。                                        |
|  [ 見る → ]                                          ● ○ ○        |
+------------------------------------------------------------------+
| SHOP BY CATEGORY                                                  |
| 購入方法から探す                                                   |
| [ロードバイク 5アイテム][MTB 4][パーツ 32][アパレル 18]             |
+------------------------------------------------------------------+
| ランキング   UPDATED 2026.08.01 07:00                              |
| (全体ランキング)(ロードバイク)(パーツ)(アパレル)                     |
| [1位カード][2位カード][3位カード][4位カード]                        |
+------------------------------------------------------------------+
| おすすめ                                                           |
| [カード][カード][カード][カード]                                    |
+------------------------------------------------------------------+
| 最近見た商品（ログイン中のみ・横スクロール）                          |
| [小カード][小カード][小カード] →                                    |
+------------------------------------------------------------------+
| 新着ニュース                                        すべて見る →   |
| 2026.08.01 [新商品] ROADSTER RC7 発売                              |
+------------------------------------------------------------------+
```

- ページの主題を示す `<h1>` は読み上げ用（`sr-only`）に置き、視覚的な見出しはメインビジュアルが担う。
- メインビジュアルは5.2秒間隔で自動的に切り替わる。ドットを押すと任意のスライドへ移動し、タイマーは張り直す。バナーが1件のときはドットを出さず自動切替もしない。ポインタが乗っている間・内部の要素にフォーカスがある間、および `prefers-reduced-motion: reduce` の環境では自動切替を止める。
- 重要なお知らせ・ランキング・おすすめ・最近見た商品・新着ニュースは、対象データが0件ならセクションごと表示しない。
- ランキングのカードに重ねる順位の数字は、読み上げでは「第N位」と読ませる。
- 重要なお知らせバーはその記事の詳細（`notices.show`）へ、各ニュース行はそのニュースの詳細（`news.show`）へ遷移する。「すべて見る →」は新着ニュース一覧（`news.index`）へ遷移する（[docs/front/news-notice.md](news-notice.md)）。
- 商品画像が未登録の場合はカテゴリごとの配色にシルエットを重ねて表示する（[docs/front/common-layout.md](common-layout.md) の `ProductVisual` が正本）。
- メインビジュアルはバナーの配色の上に汎用の車体シルエットを薄く敷く。バナーごとに図案を指定する列を持たないため、カテゴリには紐づけない。
- カテゴリ入口の各タイルは、カテゴリごとの配色にそのカテゴリのシルエット（`CategorySilhouette`）を重ねる。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/Top/Index.tsx
type Props = {
    notice: { id: number; title: string } | null;
    banners: {
        id: number;
        tag: string;
        title: string;        // 改行を含む
        subtitle: string | null;
        background: string;   // CSS の linear-gradient 値
        link_url: string | null;
    }[];
    categoryEntries: { id: number; name: string; product_count: number }[];
    rankingTabs: { key: string; label: string; category_id: number | null }[];
    rankings: Record<string, (ProductCardData & { rank_position: number })[]>;
    rankingUpdatedAt: string | null;      // 'YYYY.MM.DD HH:mm'（表示用）
    rankingUpdatedAtIso: string | null;   // ISO 8601（<time> の datetime 用）
    recommends: ProductCardData[];
    histories: ProductCardData[];         // 未ログインは空配列
    news: {
        id: number;
        published_on: string;             // 'YYYY.MM.DD'（表示用）
        published_on_iso: string;         // 'YYYY-MM-DD'（<time> の datetime 用）
        category: string;
        category_tone: { fg: string; bg: string };
        title: string;
    }[];
};
```

`ProductCardData` は [docs/front/product-index.md](product-index.md) の商品カードと同じ形。`rankings` のキーは `rankingTabs[].key`（全体は `all`、カテゴリ別はカテゴリIDの文字列）。

### 入力値バリデーションルール

なし（入力を受け取らない）。

### 各セクションのデータ取得

| セクション | 取得条件 |
|---|---|
| 重要なお知らせ | 掲載中（`Notice::displayable()`）のうち `display_start_on` の降順で1件。0件なら `null` |
| メインビジュアル | `Banner::active()` を `sort_order` 昇順で全件 |
| 購入方法から探す | `categories` を `sort_order` 昇順。件数は公開商品のみを数える |
| ランキング | 公開中の集計月（[docs/ranking.md](../ranking.md)）の `product_rankings` を `rank_position` 昇順で取得し、タブごとに上位4件。集計後に非公開・削除された商品は除外する。タブは全体を含めて最大4つで、タブに出ないカテゴリの商品は渡さない |

タブに出すカテゴリは、ランキングを持つカテゴリを `categories.sort_order` 昇順（同順は `id` 昇順）で並べ、全体タブを含めて4つに達するまで採る。販売数の多い順ではないため、どのカテゴリがタブに出るかはカテゴリの並び順で決まる。各タブの件数はそのカテゴリに属する公開商品の数が上限になる。
| おすすめ | 公開商品を `id` の降順で4件（新着をおすすめとして扱う） |
| 最近見た商品 | ログイン中のみ。公開中の商品に限定したうえで `browsing_histories` を `viewed_at` 降順で6件取得する（非公開商品があっても表示件数が減らない） |
| 新着ニュース | `News::published()` を `published_on` 降順で4件 |

### 主要な処理フロー

1. 各セクションのデータを `TopPageService` が組み立てて `Inertia::render('front/Top/Index', ...)` へ渡す。
2. ランキングは、公開中の集計月を決める → その月の順位行を取得 → 表示できる商品だけをタブごとに4件まで詰める、の順で構成する。
3. 商品カードのデータ（カテゴリ名・メイン画像・在庫の二値）は、ランキング・おすすめ・閲覧履歴のいずれも同一のロジックで組み立て、商品の件数が増えてもクエリ数が変わらないようにする。

## 業務ルール

- ランキングの切り替え時刻（毎月1日7:00）と集計条件は [docs/ranking.md](../ranking.md) に従う。
- メインビジュアルの管理画面は要件の管理メニューに含まれないため作らない。バナーはSeederで投入する。
- バナーの遷移先はサイト内の絶対パス（`/` 始まり）のみを使い、それ以外は商品一覧へ遷移させる。

## 関連ドキュメント

- [docs/ranking.md](../ranking.md) — ランキングの集計・公開タイミングの正本
- [docs/front/product-index.md](product-index.md) — 商品カードの表示データの正本
- [docs/front/product-show.md](product-show.md) — 在庫の二値判定の正本
- [docs/front/common-layout.md](common-layout.md) — `FrontLayout`・カテゴリ別グラデーションの正本
- [docs/front/news-notice.md](news-notice.md) — 「すべて見る →」「詳細 →」の遷移先である一覧画面の正本
- [docs/admin/news.md](../admin/news.md) — 新着ニュースの登録元
- [docs/admin/notice.md](../admin/notice.md) — 重要なお知らせの登録元
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/TopController.php` |
| Service | `app/Services/Front/Top/TopPageService.php` |
| Action | `app/Actions/Front/Product/BuildProductCard.php` |
| Page | `resources/js/front/Pages/Top/Index.tsx` |
| Component | `resources/js/front/Components/Top/NoticeBar.tsx` |
| Component | `resources/js/front/Components/Top/HeroSlider.tsx` |
| Component | `resources/js/front/Components/Top/CategoryEntries.tsx` |
| Component | `resources/js/front/Components/Top/RankingSection.tsx` |
| Component | `resources/js/front/Components/Top/RecommendSection.tsx` |
| Component | `resources/js/front/Components/Top/HistorySection.tsx` |
| Component | `resources/js/front/Components/Top/NewsSection.tsx` |
| Hook | `resources/js/front/hooks/useAutoSlide.ts` |
| Test | `tests/Feature/Front/Top/TopPageTest.php` |
| Test | `tests/Feature/Front/Top/TopNoticeTest.php` |
| Test | `tests/Feature/Front/Top/TopRankingTest.php` |
| Test | `tests/Feature/Front/Top/TopNewsTest.php` |

## 受け入れ条件

- 未ログインでもTOPが表示され、バナー・おすすめが返る: `tests/Feature/Front/Top/TopPageTest.php`（`未ログインでもTOPページが表示される`）
- 非公開バナーが表示されない: 同上（`非公開のバナーは表示されない`）
- おすすめが公開商品の新着順4件になる: 同上（`おすすめは公開商品の新着順で四件まで返る`）
- カテゴリ入口の件数が公開商品のみを数える: 同上（`カテゴリ入口に公開商品の件数が付く`）
- 閲覧履歴が新しい順で返り、非公開商品があっても表示件数が減らない: 同上（`ログイン中は閲覧履歴が新しい順で返る`・`非公開になった商品は閲覧履歴に出さない`・`非公開商品があっても閲覧履歴の表示件数が減らない`）
- 商品件数が増えてもクエリ数が増えない（N+1が発生しない）: 同上（`商品件数が増えてもクエリ数が増えない`）
- 重要なお知らせの掲載期間判定と0件時の扱い: `tests/Feature/Front/Top/TopNoticeTest.php`
- ランキングのタブ構成・上位4件・非公開除外・0件時の扱い・タブ数上限とタブ外カテゴリの除外: `tests/Feature/Front/Top/TopRankingTest.php`
- 新着ニュースの公開判定・件数・種別と配色: `tests/Feature/Front/Top/TopNewsTest.php`
- 重要なお知らせバー・各ニュース行から該当記事の詳細ページへ遷移する: 自動テストなし。目視確認で担保する
- メインビジュアルの自動切替（5.2秒）・ドット操作・ポインタ/フォーカス中と `prefers-reduced-motion: reduce` での停止: 自動テストなし。目視確認で担保する
- レスポンシブ（PC 21:9 / SP 4:5、カードの折り返し、横スクロール）: 自動テストなし。目視確認で担保する
- Props型定義の整合性: `npx tsc --noEmit`
