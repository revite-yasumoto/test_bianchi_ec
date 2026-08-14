# 新着ニュース管理

## 機能概要

- **対象画面・機能の目的:** フロントに表示する新着ニュースを一覧し、モーダルで作成・編集・削除する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/news` | `admin.news.index` | `auth:admin` |
| POST | `/admin/news` | `admin.news.store` | `auth:admin` |
| PUT | `/admin/news/{news}` | `admin.news.update` | `auth:admin` |
| DELETE | `/admin/news/{news}` | `admin.news.destroy` | `auth:admin` |

作成・編集は一覧画面のモーダルで行うため、`create` / `edit` の専用ページを持たない。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 管理画面の一覧・作成・編集・削除。フロント側の新着ニュース表示は単位14（TOP）・単位18（一覧ページ）で実装する。

## 使用テーブル

`news`。定義は [docs/2_database.md](../2_database.md) が正本。種別は `App\Enums\NewsCategory`（`新商品` / `お知らせ` / `商品情報`）で管理し、バッジの配色も同 Enum が持つ。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+ 新着ニュース管理 ------------------------------ [＋ 新規作成] +
+---------------------------------------------------------------+
| 掲載日     | 種別    | タイトル              | 状態  |         |
|------------|---------|-----------------------|-------|---------|
| 2026.08.10 |[新商品] | 新モデルの取り扱い…   | 公開  |編集 削除|
| 2026.08.05 |[お知らせ]| 夏季休業のお知らせ   | 非公開|編集 削除|
+---------------------------------------------------------------+
                        [前へ] [1] [2] [次へ]
```

エディタモーダル（新規作成・編集で共通）:

```
+ ニュースを作成 ---------------------------- × +
| タイトル [                                  ] |
| 掲載日 [2026-08-12]    種別 [新商品      ▼]  |
| 公開状態 [公開 ▼]                            |
| 本文 [                              （5行）] |
| [               保存する               ]     |
+-----------------------------------------------+
```

- 「＋ 新規作成」はページヘッダーに置き、一覧の「編集」と同じモーダルを開く。
- 種別は Enum の配色でバッジ表示する。状態は「公開」「非公開」のテキストで表示する。
- 削除は確認モーダル（`ConfirmDialog`）を経て実行する。保存・削除の完了後はトースト（「保存しました」「削除しました」）を表示する。
- バリデーションエラーは各項目の下に表示し、モーダルは開いたままにする。
- 0件のときは「新着ニュースが登録されていません」を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type NewsRow = {
    id: number;
    published_on: string;        // 'YYYY.MM.DD'（表示用）
    published_on_input: string;  // 'YYYY-MM-DD'（エディタの date 入力用）
    category: string;            // '新商品' / 'お知らせ' / '商品情報'
    category_tone: Tone;         // バッジの配色（NewsCategory::color()）
    title: string;
    body: string;
    is_published: boolean;
    state_label: string;         // '公開' / '非公開'
};

type Props = {
    news: Paginated<NewsRow>;
    categoryOptions: string[];   // NewsCategory の値の一覧
};
```

- **入力値バリデーションルール（`Admin\News\SaveNewsRequest`。作成・更新で共通）:**

| 項目 | ルール |
|---|---|
| `published_on` | 必須・`Y-m-d` 形式 |
| `category` | 必須・`App\Enums\NewsCategory` の値のいずれか |
| `title` | 必須・文字列・255文字以下 |
| `body` | 必須・文字列・10,000文字以下 |
| `is_published` | 必須・boolean |

- **主要な処理フロー:**

**一覧（`index()`）:** 掲載日の降順 → IDの降順で1ページ50件のページネーション。公開・非公開を問わず全件を表示する。

**作成（`store()`）／更新（`update()`）:** エディタモーダルから送信 → 保存 → 直前の一覧へ戻り、トーストを表示する。

**削除（`destroy()`）:** 確認モーダル → 物理削除 → 直前の一覧へ戻り、トーストを表示する。

## 業務ルール

- 公開・非公開は `is_published` のみで制御する。`published_on` は表示用の日付であり、未来日でも `is_published` が true ならフロントに表示される（予約公開は持たない）。
- 本文はプレーンテキストとして扱い、HTMLタグを解釈しない。改行はフロント側で保持して表示する。

## 関連ドキュメント

- [docs/admin/notice.md](notice.md) — 同じ管理メニュー群の重要なお知らせ管理
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`DataTable`・`Pagination`・`ConfirmDialog`・トーストの正本
- [docs/2_database.md](../2_database.md) — `news` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Model | `app/Models/News.php` |
| Enum | `app/Enums/NewsCategory.php` |
| Controller | `app/Http/Controllers/Admin/NewsController.php` |
| FormRequest | `app/Http/Requests/Admin/News/SaveNewsRequest.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/News/Index.tsx` |
| Component | `resources/js/admin/Components/News/NewsManager.tsx` |
| Component | `resources/js/admin/Components/News/NewsEditorModal.tsx` |
| Component | `resources/js/shared/Components/Modal.tsx` |
| Test | `tests/Feature/Admin/News/NewsIndexTest.php` |
| Test | `tests/Feature/Admin/News/NewsStoreTest.php` |
| Test | `tests/Feature/Admin/News/NewsUpdateTest.php` |
| Test | `tests/Feature/Admin/News/NewsDestroyTest.php` |

## 受け入れ条件

- 公開・非公開の両方が一覧に表示される: `tests/Feature/Admin/News/NewsIndexTest.php`（`公開と非公開の両方が一覧に表示される`）
- 一覧が掲載日の降順で並ぶ: 同上（`一覧は掲載日の降順で並ぶ`）
- 掲載日・種別が表示用の形式で渡され、種別の選択肢が渡される: 同上（`掲載日と種別が表示用の形式で渡される`・`種別の選択肢が渡される`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- ニュースを公開・非公開の指定付きで作成できる: `tests/Feature/Admin/News/NewsStoreTest.php`（`ニュースを作成できる`・`非公開として作成できる`）
- 必須項目の欠落・許可外の種別・掲載日の形式不正・本文の上限超過を弾く: 同上（`タイトルが未入力では作成できない`・`許可されていない種別は弾かれる`・`掲載日の形式が不正だと作成できない`・`本文が上限を超えると作成できない`）
- 未認証は作成できない: 同上（`未認証は作成できない`）
- ニュースを更新でき、不正な値では更新されない: `tests/Feature/Admin/News/NewsUpdateTest.php`（`ニュースを更新できる`・`不正な値では更新されない`・`未認証は更新できない`）
- ニュースを削除でき、未認証は削除できない: `tests/Feature/Admin/News/NewsDestroyTest.php`（`ニュースを削除できる`・`未認証は削除できない`）
- エディタモーダルの開閉・確認モーダル・トースト表示: 自動テストなし。目視確認で担保する
