# 単位18: フロント - サポート・法的ページ・お問い合わせ

依存: 単位02, 単位11

最後の単位。フッターからアクセスするページ群を揃え、サイト全体のリンク切れを解消する。

## スコープ

- 新着ニュース一覧（本文の展開表示）
- 重要なお知らせ一覧（本文の展開表示）
- 買い物ガイド
- 特定商取引法に基づく表記
- プライバシーポリシー
- 利用規約
- お問い合わせフォーム（商品詳細からの商品名の自動入力に対応）
- ヘッダー・フッターの全リンクを実ルートへ接続する

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

## ルーティング（`routes/web.php`）

すべて認証不要。

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/news` | `news.index` | 新着ニュース一覧 |
| GET | `/notices` | `notices.index` | 重要なお知らせ一覧 |
| GET | `/guide` | `guide` | 買い物ガイド |
| GET | `/legal/tokushoho` | `legal.tokushoho` | 特定商取引法に基づく表記 |
| GET | `/legal/privacy` | `legal.privacy` | プライバシーポリシー |
| GET | `/legal/terms` | `legal.terms` | 利用規約 |
| GET | `/contact` | `contact` | お問い合わせフォーム |
| POST | `/contact` | `contact.store` | お問い合わせ送信 |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/NewsController.php` | `index()` |
| Controller | `app/Http/Controllers/Front/NoticeController.php` | `index()` |
| Controller | `app/Http/Controllers/Front/StaticPageController.php` | `guide()` / `tokushoho()` / `privacy()` / `terms()` |
| Controller | `app/Http/Controllers/Front/ContactController.php` | `create()` / `store()` |
| FormRequest | `app/Http/Requests/Front/Contact/StoreContactRequest.php` | お問い合わせのバリデーション |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/front/Pages/News/Index.tsx` | 日付＋種別バッジ＋タイトルの行リスト。クリックで本文を開閉 |
| Page | `resources/js/front/Pages/Notice/Index.tsx` | タイトル＋掲載期間の行リスト。クリックで本文を開閉 |
| Page | `resources/js/front/Pages/Static/Guide.tsx` | 買い物ガイド（送料・支払い方法・発送日数・返品の案内） |
| Page | `resources/js/front/Pages/Static/Tokushoho.tsx` | 特定商取引法に基づく表記（定義リスト形式） |
| Page | `resources/js/front/Pages/Static/Privacy.tsx` | プライバシーポリシー |
| Page | `resources/js/front/Pages/Static/Terms.tsx` | 利用規約 |
| Page | `resources/js/front/Pages/Contact/Create.tsx` | お名前・メールアドレス・対象商品・お問い合わせ内容＋送信 |
| Component | `resources/js/front/Components/Support/ArticleList.tsx` | 開閉可能な記事リスト（ニュース・お知らせで共用） |
| Component | `resources/js/front/Components/Support/StaticPageLayout.tsx` | 見出し＋本文の共通枠（静的4ページで共用） |
| Component | `resources/js/front/Components/Header.tsx` | **修正**: 「新着ニュース」「買い物ガイド」「お問い合わせ」のリンクを実ルートへ接続 |
| Component | `resources/js/front/Components/Footer.tsx` | **修正**: SHOPPING / SUPPORT / LEGAL の全リンクを実ルートへ接続 |
| Component | `resources/js/front/Components/Top/NoticeBar.tsx` | **修正**: 重要なお知らせ一覧へのリンクに接続 |
| Component | `resources/js/front/Components/Top/NewsSection.tsx` | **修正**: 「すべて見る →」を `news.index` へ接続 |
| Component | `resources/js/front/Components/Product/ShippingInfoModal.tsx` | **修正**: 買い物ガイドへのリンクを追加 |

### 静的ページの本文

要件で「デモのため内容はダミー」と定められている。本文は React コンポーネント内に直接記述する（DBテーブル・管理UIは作らない）。会社名・住所・電話番号等は**架空値**を使い、実在の企業情報を書かない。

## インターフェース ＆ データロジック

### Props の型

```ts
// News/Index.tsx
type NewsArticle = {
  id: number;
  published_on: string;      // 'YYYY.MM.DD'
  category: string;          // '新商品' / 'お知らせ' / '商品情報'
  title: string;
  body: string;
};
type Props = { news: Paginated<NewsArticle> };

// Notice/Index.tsx
type NoticeArticle = {
  id: number;
  title: string;
  body: string;
  period_label: string;      // '2026.08.05 - 2026.08.20'
};
type Props = { notices: Paginated<NoticeArticle> };

// Contact/Create.tsx
type Props = {
  defaults: {
    name: string;            // ログイン中なら会員氏名、未ログインは空
    email: string;           // ログイン中なら会員メール、未ログインは空
    product_name: string;    // クエリ ?product_name= の値
  };
};
```

### バリデーション

`StoreContactRequest`:

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `email` | 必須・メール形式・最大191 |
| `product_name` | 任意・文字列・最大255 |
| `body` | 必須・文字列・**10文字以上**・最大5000 |

`body` の下限10文字はモックのバリデーションに合わせる。

### 主要な処理フロー

**新着ニュース一覧**: `News::published()` を `published_on` 降順 → `id` 降順でページネーション（1ページ20件）。非公開のニュースは表示しない。

**重要なお知らせ一覧**: `Notice::displayable()`（掲載期間内）を `display_start_on` 降順でページネーション（1ページ20件）。掲載期間外のお知らせは表示しない。

**お問い合わせフォームの表示**:
1. クエリ `?product_name=` があれば「対象商品」の初期値に入れる（商品詳細の「この商品について問い合わせる」からの遷移）
2. ログイン中なら「お名前」「メールアドレス」に会員情報を初期値として入れる

**お問い合わせ送信**:
1. バリデーション
2. `contacts` に保存する（ログイン中なら `user_id` を記録する）
3. 「送信しました。3営業日以内にご返信いたします。」を成功メッセージとして表示する
4. メール送信は行わない

### 本文の表示

ニュース・お知らせの本文はプレーンテキストとして扱い、改行を保持して表示する（`white-space: pre-line`）。`dangerouslySetInnerHTML` は使わない。

## 業務ルール

- 静的4ページ（買い物ガイド・特定商取引法・プライバシーポリシー・利用規約）の内容はダミーであり、管理画面からの編集はできない
- 会員登録フォームの利用規約・プライバシーポリシーへのリンクを、本単位で実装したページへ接続する（単位02で同意チェックのみ実装済み）
- お問い合わせはDBに保存するのみで、メール送信・管理画面での閲覧は実装しない
- 新着ニュース・重要なお知らせに記事ごとの詳細ページは設けない（一覧内で本文を開閉する）

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/News/NewsIndexTest.php` | 公開ニュースのみ表示されること／非公開ニュースが表示されないこと／掲載日の降順／未ログインでも閲覧できること |
| `tests/Feature/Front/Notice/NoticeIndexTest.php` | 掲載期間内のお知らせのみ表示されること／掲載開始前・掲載終了後のお知らせが表示されないこと／境界値（掲載開始日当日・掲載終了日当日）が表示されること |
| `tests/Feature/Front/StaticPageTest.php` | 買い物ガイド・特定商取引法・プライバシーポリシー・利用規約の4ページが未ログインで200を返すこと |
| `tests/Feature/Front/Contact/ContactCreateTest.php` | クエリの `product_name` が初期値に入ること／ログイン中に会員の氏名・メールアドレスが初期値に入ること／未ログイン時は空になること |
| `tests/Feature/Front/Contact/ContactStoreTest.php` | 正常送信で `contacts` に保存されること／ログイン中は `user_id` が記録されること／未ログインは `user_id` が `null` になること／お名前・メールアドレスの必須／メール形式／本文10文字未満を弾くこと（境界値: 9文字は不可・10文字は可） |
| `tests/Feature/Front/NavigationLinkTest.php` | ヘッダー・フッターのリンク先ルートがすべて存在し200を返すこと（リンク切れの検出） |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **お問い合わせフォームの実装**: 要件で「実装有無を引き続き検討」。推奨=**実装し `contacts` に保存する**（メール送信・管理画面での閲覧は行わない）。管理画面でお問い合わせを一覧できるようにする場合は別単位で追加する。
2. **ニュース・お知らせの詳細ページ**: 要件のフロント画面一覧に含まれない。推奨=**一覧内で本文を開閉する**（詳細ページを作らない）。
3. **静的ページの管理画面からの編集**: 要件の管理画面10メニューに含まれない。推奨=**実装しない**（本文をコンポーネントに直接記述する）。
4. **静的ページの内容**: 要件で「デモのため内容はダミー」。会社名・住所・電話番号・代表者名はすべて**架空値**を使う。実在の企業情報・クライアント提供の実データは書かない。
5. **お問い合わせのスパム対策**: 要件に記載なし。推奨=**レート制限のみ実装する**（同一IPから10回／時）。CAPTCHA は導入しない。
6. **お問い合わせの自動返信メール**: 送信しない。
7. **ページネーション**: 推奨=ニュース・お知らせともに1ページ20件。
8. **買い物ガイドの内容**: 送料（都道府県別）・支払い方法（銀行振込／代引き）・発送日数・送料無料条件を、`shipping_settings` と `ec_settings` の**現在値から動的に生成する**（設定変更が案内に自動反映される）。返品・交換の案内はダミーテキストとする。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/front/news-notice.md`（新着ニュース一覧・重要なお知らせ一覧）
  - `docs/front/static-pages.md`（買い物ガイド・法的3ページ）
  - `docs/front/contact.md`（お問い合わせ）
- `docs/front/common-layout.md` の `## ソースファイル` と本文を、ヘッダー・フッターのリンク接続に合わせて同期する
- 全単位の完了後、`docs/reports/implementation-tracker.md` を削除する（情報は仕様書と git 履歴に揃うため）
- 本計画ファイルを削除する
