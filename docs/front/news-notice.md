# フロント 新着ニュース一覧・重要なお知らせ一覧

## 機能概要

- **対象画面・機能の目的:** 会員・訪問者が、掲載中の新着ニュースと重要なお知らせを一覧で読む。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/news` | `news.index` | 新着ニュース一覧 |
| GET | `/notices` | `notices.index` | 重要なお知らせ一覧 |

- **アクセス権限・ミドルウェア:** なし（未ログインで閲覧できる）。
- **本ドキュメントのスコープ:** 一覧の表示と本文の開閉。記事の登録・編集は [docs/admin/news.md](../admin/news.md)・[docs/admin/notice.md](../admin/notice.md) が正本。TOPの新着ニュース欄・お知らせバーは [docs/front/top.md](top.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `news` | 公開中（`is_published = true`）のニュースを表示する |
| `notices` | 掲載期間内のお知らせを表示する |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
新着ニュース                              （h1）
────────────────────────────────────────
2026.08.13  [新商品]  架空ジャージを発売  ＋   ← クリックで開閉
2026.08.05  [お知らせ] 夏季休業のご案内   ＋
────────────────────────────────────────
（1ページ20件。以降はページ送り）
```

- 行をクリックすると本文が開き、記号が「＋」から「−」に変わる。開閉は `details` / `summary` 要素が担うため、状態管理と `aria-expanded` を自前で持たない。
- 重要なお知らせは日付・種別の代わりに「重要」バッジと掲載期間（`2026.08.05 - 2026.08.20`）を出す。掲載日・掲載期間は `time` 要素でマークアップし、`datetime` にISO 8601の日付を入れる。
- 本文はプレーンテキストとして扱い、改行を保持して表示する（`white-space: pre-line`）。HTMLとして解釈しない。
- 表示できる記事が0件のときは、ニュースは「お知らせはまだありません。」、お知らせは「掲載中のお知らせはありません。」を出す。
- 幅は最大760pxで中央寄せにし、SPでは左右22pxの余白を取る。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/News/Index.tsx
type NewsArticle = {
    id: number;
    published_on: string;        // 2026.08.13
    published_on_iso: string;    // time 要素の datetime 用
    category: string;            // 新商品 / お知らせ / 商品情報
    category_tone: Tone;         // { fg, bg }。NewsCategory::color() 由来
    title: string;
    body: string;
};
type Props = { news: Paginated<NewsArticle> };

// resources/js/front/Pages/Notice/Index.tsx
type NoticeArticle = {
    id: number;
    title: string;
    body: string;
    period_start: string;        // 2026.08.05
    period_start_iso: string;    // time 要素の datetime 用
    period_end: string;
    period_end_iso: string;
};
type Props = { notices: Paginated<NoticeArticle> };

// resources/js/front/Components/Support/ArticleList.tsx
export type Article = {
    id: number;
    title: string;
    body: string;
    /** タイトルの左に出す補助情報（掲載日・掲載期間など） */
    meta: ReactNode;
};
```

### 入力値バリデーションルール

なし（入力を受け取らない）。

### 主要な処理フロー

**新着ニュース一覧**

1. `is_published = true` のニュースを、`published_on` 降順 → `id` 降順で1ページ20件に区切る。
2. 種別のバッジ配色は `NewsCategory::color()` から得る。

**重要なお知らせ一覧**

1. 掲載期間内（`display_start_on <= 当日 <= display_end_on`）のお知らせを、`display_start_on` 降順 → `id` 降順で1ページ20件に区切る。掲載開始日・掲載終了日の当日は掲載中として扱う。
2. 掲載期間の判定規則は [docs/admin/notice.md](../admin/notice.md) が正本。

## 業務ルール

- 記事ごとの詳細ページは設けない。本文は一覧内で開閉する。

## 関連ドキュメント

- [docs/admin/news.md](../admin/news.md) — ニュースの登録・編集と公開フラグの正本
- [docs/admin/notice.md](../admin/notice.md) — お知らせの登録・編集と掲載期間の判定規則の正本
- [docs/front/top.md](top.md) — TOPの新着ニュース欄・重要なお知らせバー（本一覧への導線）
- [docs/front/common-layout.md](common-layout.md) — ヘッダー・フッターのナビゲーションの正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/NewsController.php` |
| Controller | `app/Http/Controllers/Front/NoticeController.php` |
| Page | `resources/js/front/Pages/News/Index.tsx` |
| Page | `resources/js/front/Pages/Notice/Index.tsx` |
| Component | `resources/js/front/Components/Support/ArticleList.tsx` |
| Test | `tests/Feature/Front/News/NewsIndexTest.php` |
| Test | `tests/Feature/Front/Notice/NoticeIndexTest.php` |

## 受け入れ条件

- 未ログインで新着ニュースを閲覧できる: `tests/Feature/Front/News/NewsIndexTest.php`（`未ログインでも新着ニュースを閲覧できる`）
- 非公開のニュースが表示されない: 同上（`非公開のニュースは表示されない`）
- 掲載日の新しい順に並ぶ: 同上（`掲載日の新しい順に並ぶ`）
- 本文と種別が一覧に渡る: 同上（`本文と種別が一覧に渡される`）
- 1ページ20件で区切られる: 同上（`一ページあたり二十件までで区切られる`）
- 記事が0件でも表示できる: 同上（`公開中のニュースが無くても閲覧できる`）
- 未ログインで掲載中のお知らせを閲覧できる: `tests/Feature/Front/Notice/NoticeIndexTest.php`（`未ログインでも掲載中のお知らせを閲覧できる`）
- 掲載期間外のお知らせが表示されない: 同上（`掲載開始前のお知らせは表示されない`・`掲載終了後のお知らせは表示されない`）
- 掲載開始日・掲載終了日の当日が表示される: 同上（`掲載開始日の当日は表示される`・`掲載終了日の当日は表示される`）
- 掲載期間が表示用と機械可読の双方で渡る: 同上（`掲載期間が表示用と機械可読の双方で渡される`）
- 掲載中のお知らせが0件でも表示できる: 同上（`掲載中のお知らせが無くても閲覧できる`）
- 行クリックでの本文の開閉・開閉記号の切り替え: 自動テストなし。目視確認で担保する
