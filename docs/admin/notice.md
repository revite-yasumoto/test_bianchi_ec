# 重要なお知らせ管理

本ドキュメントは、重要なお知らせの**掲載状態の算出規則の正本**である。フロント側（単位14・18）は本ドキュメントの規則に従い、本文を再掲せずここへリンクする。

## 機能概要

- **対象画面・機能の目的:** フロントTOPの上部に表示する重要なお知らせを一覧し、モーダルで作成・編集・削除する。掲載期間で表示を制御する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/notices` | `admin.notices.index` | `auth:admin` |
| POST | `/admin/notices` | `admin.notices.store` | `auth:admin` |
| PUT | `/admin/notices/{notice}` | `admin.notices.update` | `auth:admin` |
| DELETE | `/admin/notices/{notice}` | `admin.notices.destroy` | `auth:admin` |

作成・編集は一覧画面のモーダルで行うため、`create` / `edit` の専用ページを持たない。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 管理画面の一覧・作成・編集・削除と、掲載状態の算出規則。フロント側の表示は単位14（TOP）・単位18（一覧ページ）で実装する。

## 使用テーブル

`notices`。定義は [docs/2_database.md](../2_database.md) が正本。掲載状態を保持するカラムは持たず、掲載期間から算出する。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
+ 重要なお知らせ管理 ---------------------------- [＋ 新規作成] +
+---------------------------------------------------------------+
| タイトル              | 掲載期間                |状態  |       |
|-----------------------|-------------------------|------|-------|
| システムメンテナンス… | 2026.08.10 - 2026.08.20 |[掲載中]|編集 削除|
| 年末年始の配送について| 2026.12.20 - 2027.01.05 |[予約]  |編集 削除|
+---------------------------------------------------------------+
掲載期間内のお知らせがフロントTOPの上部にリンク形式で表示されます。
                        [前へ] [1] [2] [次へ]
```

エディタモーダル（新規作成・編集で共通）:

```
+ お知らせを作成 ---------------------------- × +
| タイトル [                                  ] |
| 掲載開始 [2026-08-10]  掲載終了 [2026-08-20] |
| 本文 [                              （5行）] |
| [               保存する               ]     |
+-----------------------------------------------+
```

- 「＋ 新規作成」はページヘッダーに置き、一覧の「編集」と同じモーダルを開く。
- 掲載状態はバッジで表示する（掲載中・予約・掲載終了）。
- 削除は確認モーダル（`ConfirmDialog`）を経て実行する。保存・削除の完了後はトースト（「保存しました」「削除しました」）を表示する。
- バリデーションエラーは各項目の下に表示し、モーダルは開いたままにする。
- 0件のときは「重要なお知らせが登録されていません」を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type NoticeRow = {
    id: number;
    title: string;
    body: string;
    display_start_on: string;   // 'YYYY-MM-DD'（エディタの date 入力用）
    display_end_on: string;     // 'YYYY-MM-DD'
    period_label: string;       // '2026.08.05 - 2026.08.20'
    state: 'displaying' | 'scheduled' | 'ended';
    state_label: string;        // '掲載中' / '予約' / '掲載終了'
    state_tone: Tone;           // バッジの配色（NoticeState::color()）
};

type Props = { notices: Paginated<NoticeRow> };
```

- **入力値バリデーションルール（`Admin\Notice\SaveNoticeRequest`。作成・更新で共通）:**

| 項目 | ルール |
|---|---|
| `title` | 必須・文字列・255文字以下 |
| `body` | 必須・文字列・10,000文字以下 |
| `display_start_on` | 必須・`Y-m-d` 形式 |
| `display_end_on` | 必須・`Y-m-d` 形式・`display_start_on` 以降（同日可） |

- **掲載状態の算出（`App\Enums\NoticeState` / `Notice::state()`）:**

| 条件 | 状態 | ラベル |
|---|---|---|
| `display_start_on` <= 当日 <= `display_end_on` | `displaying` | 掲載中 |
| 当日 < `display_start_on` | `scheduled` | 予約 |
| `display_end_on` < 当日 | `ended` | 掲載終了 |

掲載開始日・掲載終了日の当日は掲載中に含める。`Notice::scopeDisplayable()` が「掲載中」の条件をクエリとして表し、フロント側の取得はこのスコープのみを使う。

- **主要な処理フロー:**

**一覧（`index()`）:** 掲載開始日の降順 → IDの降順で1ページ50件のページネーション。掲載状態を問わず全件を表示する。

**作成（`store()`）／更新（`update()`）:** エディタモーダルから送信 → 保存 → 直前の一覧へ戻り、トーストを表示する。

**削除（`destroy()`）:** 確認モーダル → 物理削除 → 直前の一覧へ戻り、トーストを表示する。

## 業務ルール

- 掲載制御は掲載期間のみで行い、公開フラグに相当するカラムを持たない。
- 掲載中のお知らせが複数ある場合、フロントTOPには掲載開始日が最も新しい1件のみを表示する（単位14）。リンク先は重要なお知らせ一覧ページ（単位18）。
- 本文はプレーンテキストとして扱い、HTMLタグを解釈しない。改行はフロント側で保持して表示する。

## 関連ドキュメント

- [docs/admin/news.md](news.md) — 同じ管理メニュー群の新着ニュース管理
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`DataTable`・`Pagination`・`ConfirmDialog`・トーストの正本
- [docs/2_database.md](../2_database.md) — `notices` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Model | `app/Models/Notice.php` |
| Enum | `app/Enums/NoticeState.php` |
| Controller | `app/Http/Controllers/Admin/NoticeController.php` |
| FormRequest | `app/Http/Requests/Admin/Notice/SaveNoticeRequest.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Notice/Index.tsx` |
| Component | `resources/js/admin/Components/Notice/NoticeManager.tsx` |
| Component | `resources/js/admin/Components/Notice/NoticeEditorModal.tsx` |
| Component | `resources/js/shared/Components/Modal.tsx` |
| Test | `tests/Feature/Admin/Notice/NoticeIndexTest.php` |
| Test | `tests/Feature/Admin/Notice/NoticeStoreTest.php` |
| Test | `tests/Feature/Admin/Notice/NoticeUpdateTest.php` |
| Test | `tests/Feature/Admin/Notice/NoticeDestroyTest.php` |
| Test | `tests/Unit/Models/NoticeScopeTest.php` |

## 受け入れ条件

- 掲載中・予約・掲載終了が当日日付から算出される: `tests/Feature/Admin/Notice/NoticeIndexTest.php`（`掲載期間から掲載中と予約と掲載終了が算出される`）
- 掲載開始日・掲載終了日の当日が掲載中になる: 同上（`掲載開始日の当日は掲載中になる`・`掲載終了日の当日は掲載中になる`）
- 掲載期間がラベル形式で渡される: 同上（`掲載期間がラベル形式で渡される`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- お知らせを作成でき、掲載開始日と掲載終了日が同日でも作成できる: `tests/Feature/Admin/Notice/NoticeStoreTest.php`（`お知らせを作成できる`・`掲載開始日と掲載終了日が同日でも作成できる`）
- 掲載終了日が掲載開始日より前・必須項目の欠落・日付形式の不正を弾く: 同上（`掲載終了日が掲載開始日より前だと作成できない`・`タイトルが未入力では作成できない`・`掲載開始日の形式が不正だと作成できない`）
- 未認証は作成できない: 同上（`未認証は作成できない`）
- お知らせを更新でき、期間が逆転する更新は弾かれる: `tests/Feature/Admin/Notice/NoticeUpdateTest.php`（`お知らせを更新できる`・`掲載終了日が掲載開始日より前だと更新されない`・`未認証は更新できない`）
- お知らせを削除でき、未認証は削除できない: `tests/Feature/Admin/Notice/NoticeDestroyTest.php`（`お知らせを削除できる`・`未認証は削除できない`）
- `displayable()` が掲載期間内のレコードのみを返し、境界日を含む: `tests/Unit/Models/NoticeScopeTest.php`（`掲載期間内のお知らせだけが取得される`・`掲載開始日と掲載終了日の当日は掲載期間に含まれる`・`掲載状態が当日日付から算出される`）
- エディタモーダルの開閉・確認モーダル・トースト表示: 自動テストなし。目視確認で担保する
