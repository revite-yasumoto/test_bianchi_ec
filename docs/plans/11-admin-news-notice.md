# 単位11: 管理画面 - 新着ニュース管理・重要なお知らせ管理

依存: 単位03

## スコープ

- 新着ニュース管理: 一覧（掲載日・種別・タイトル・状態）・新規作成・編集・削除
- 重要なお知らせ管理: 一覧（タイトル・掲載期間・状態）・新規作成・編集・削除・掲載期間の設定
- どちらもモーダル形式のエディタで作成／編集する（モックに準拠）
- フロント側の表示は単位14（TOP）・単位18（一覧ページ）

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest） | `laravel-set:laravel` |
| クエリビルダ | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/news` | `admin.news.index` | 新着ニュース一覧 |
| POST | `/admin/news` | `admin.news.store` | 新着ニュース作成 |
| PUT | `/admin/news/{news}` | `admin.news.update` | 新着ニュース更新 |
| DELETE | `/admin/news/{news}` | `admin.news.destroy` | 新着ニュース削除 |
| GET | `/admin/notices` | `admin.notices.index` | 重要なお知らせ一覧 |
| POST | `/admin/notices` | `admin.notices.store` | 重要なお知らせ作成 |
| PUT | `/admin/notices/{notice}` | `admin.notices.update` | 重要なお知らせ更新 |
| DELETE | `/admin/notices/{notice}` | `admin.notices.destroy` | 重要なお知らせ削除 |

作成・編集はモーダルで行うため、`create` / `edit` の専用ページは作らない。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/NewsController.php` | `index()` / `store()` / `update()` / `destroy()` |
| Controller | `app/Http/Controllers/Admin/NoticeController.php` | `index()` / `store()` / `update()` / `destroy()` |
| FormRequest | `app/Http/Requests/Admin/News/StoreNewsRequest.php` | 掲載日・種別・タイトル・本文・公開状態 |
| FormRequest | `app/Http/Requests/Admin/News/UpdateNewsRequest.php` | 同上 |
| FormRequest | `app/Http/Requests/Admin/Notice/StoreNoticeRequest.php` | タイトル・本文・掲載期間 |
| FormRequest | `app/Http/Requests/Admin/Notice/UpdateNoticeRequest.php` | 同上 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/News/Index.tsx` | 一覧テーブル＋「＋ 新規作成」ボタン＋エディタモーダル |
| Page | `resources/js/admin/Pages/Notice/Index.tsx` | 一覧テーブル＋「＋ 新規作成」ボタン＋エディタモーダル＋注記 |
| Component | `resources/js/admin/Components/News/NewsEditorModal.tsx` | タイトル・種別select・本文textarea・公開状態 |
| Component | `resources/js/admin/Components/Notice/NoticeEditorModal.tsx` | タイトル・掲載開始／終了日・本文 |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「新着ニュース管理」「重要なお知らせ管理」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
// News/Index.tsx
type NewsRow = {
  id: number;
  published_on: string;       // 'YYYY.MM.DD'
  published_on_input: string; // 'YYYY-MM-DD'（エディタ用）
  category: string;           // '新商品' / 'お知らせ' / '商品情報'
  title: string;
  body: string;
  is_published: boolean;
  state_label: string;        // '公開' / '非公開'
};
type Props = { news: Paginated<NewsRow>; categoryOptions: string[] };

// Notice/Index.tsx
type NoticeRow = {
  id: number;
  title: string;
  body: string;
  display_start_on: string;   // 'YYYY-MM-DD'
  display_end_on: string;     // 'YYYY-MM-DD'
  period_label: string;       // '2026.08.05 - 2026.08.20'
  state: 'displaying' | 'scheduled' | 'ended';
  state_label: string;        // '掲載中' / '予約' / '掲載終了'
};
type Props = { notices: Paginated<NoticeRow> };
```

### バリデーション

`StoreNewsRequest` / `UpdateNewsRequest`:

| 項目 | ルール |
|---|---|
| `published_on` | 必須・日付（`Y-m-d`） |
| `category` | 必須・`App\Enums\NewsCategory` の値のいずれか |
| `title` | 必須・文字列・最大255 |
| `body` | 必須・文字列・最大10000 |
| `is_published` | 必須・boolean |

`StoreNoticeRequest` / `UpdateNoticeRequest`:

| 項目 | ルール |
|---|---|
| `title` | 必須・文字列・最大255 |
| `body` | 必須・文字列・最大10000 |
| `display_start_on` | 必須・日付（`Y-m-d`） |
| `display_end_on` | 必須・日付（`Y-m-d`）・`display_start_on` 以降 |

### 掲載状態の算出（重要なお知らせ）

カラムとして保存せず、当日日付と掲載期間から算出する。

| 条件 | 状態 |
|---|---|
| `display_start_on` <= 当日 <= `display_end_on` | 掲載中 `displaying` |
| 当日 < `display_start_on` | 予約 `scheduled` |
| `display_end_on` < 当日 | 掲載終了 `ended` |

`Notice` モデルの `displayable()` スコープが「掲載中」の条件を表す。フロント側（単位14・18）はこのスコープのみを使う。

### 主要な処理フロー

**新着ニュース一覧**: `published_on` の降順 → `id` の降順でページネーション（1ページ50件）。公開／非公開を問わず全件を表示する。

**新着ニュース作成／更新**: エディタモーダルで入力 → 保存 → 一覧へリダイレクトし「保存しました」をフラッシュ

**削除（両画面共通）**: 確認モーダル（「削除した内容は復元できません。フロント側の表示からも即時に削除されます。」）→ 削除 → 「削除しました」をフラッシュ

**重要なお知らせ一覧**: 掲載開始日の降順でページネーション。掲載状態のバッジを表示する。

## 業務ルール

- 新着ニュースの公開／非公開は `is_published` で制御する。掲載日（`published_on`）は表示用の日付であり、公開制御には使わない（未来日の掲載日でも `is_published = true` なら公開される）
- 重要なお知らせの掲載制御は掲載期間で行う。`is_published` に相当するカラムは持たない
- フロントTOPには掲載中の重要なお知らせのうち、掲載開始日が最も新しい1件をリンク形式で表示する（単位14）
- 本文は改行を保持して表示する。HTMLタグは受け付けない（プレーンテキストとして扱い、表示時にエスケープする）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/News/NewsIndexTest.php` | 公開・非公開の両方が一覧に出ること／掲載日の降順／未認証のリダイレクト |
| `tests/Feature/Admin/News/NewsStoreTest.php` | 正常作成／必須項目の欠落／種別の許可外の値を弾くこと／本文の最大長超過 |
| `tests/Feature/Admin/News/NewsUpdateTest.php` | 更新できること |
| `tests/Feature/Admin/News/NewsDestroyTest.php` | 削除できること |
| `tests/Feature/Admin/Notice/NoticeIndexTest.php` | 掲載中・予約・掲載終了の状態ラベルが当日日付から正しく算出されること（`Carbon::setTestNow()` を使う）／境界値（掲載開始日当日・掲載終了日当日）が「掲載中」になること |
| `tests/Feature/Admin/Notice/NoticeStoreTest.php` | 正常作成／掲載終了日が掲載開始日より前のときエラーになること／同日は許可されること |
| `tests/Feature/Admin/Notice/NoticeDestroyTest.php` | 削除できること |
| `tests/Unit/Models/NoticeScopeTest.php` | `displayable()` スコープが掲載期間内のレコードのみを返すこと |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **本文のリッチテキスト対応**: 実装しない。プレーンテキスト（改行のみ保持）として扱う。要件に記載がなく、`dangerouslySetInnerHTML` を使わないため XSS のリスクを持ち込まない。
2. **新着ニュースの詳細ページ**: 要件のフロント画面一覧は「新着ニュース」（一覧）のみで、記事ごとの詳細ページは含まれない。推奨=**一覧のみとし、詳細ページは作らない**（本文は一覧の行を開いてその場で表示する）。単位18で決める。
3. **重要なお知らせのリンク先**: 要件は「TOPの上寄りにリンク形式で表示（リンク遷移に対応）」。推奨=**単位18で実装する重要なお知らせ一覧ページへ遷移する**。
4. **新着ニュースの掲載日と公開制御**: 推奨=`is_published` のみで公開を制御し、`published_on` は表示用日付とする（予約公開は実装しない）。
5. **掲載中のお知らせが複数ある場合**: 推奨=TOPには掲載開始日が最も新しい1件のみを表示する。
6. **ページネーション**: 推奨=1ページ50件。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/news.md`
  - `docs/admin/notice.md`（掲載状態の算出規則の**正本**。単位14・18の仕様書からリンクする）
- 本計画ファイルを削除し、トラッカーの状態を更新する
