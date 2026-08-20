# お問い合わせ管理

本ドキュメントは**対応ステータスの区分と更新規則**、および**問い合わせのCSVエクスポート**の正本である。フォームの表示・送信は [docs/front/contact.md](../front/contact.md) が正本。

## 機能概要

- **対象画面・機能の目的:** フロントから送信された問い合わせを一覧・詳細で確認し、対応状況を記録する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/contacts` | `admin.contacts.index` | `auth:admin` |
| GET | `/admin/contacts/csv/export` | `admin.contacts.csv.export` | `auth:admin` |
| GET | `/admin/contacts/{contact}` | `admin.contacts.show` | `auth:admin` |
| PUT | `/admin/contacts/{contact}` | `admin.contacts.update` | `auth:admin` |

CSVのルートは `contacts/{contact}` として解釈されないよう、詳細ルートより**先に**定義する（[docs/admin/csv.md](csv.md) と同じ規則）。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 一覧（タブ・絞り込み・ページネーション）、詳細、対応状況の更新、CSVエクスポート。

## 使用テーブル

`contacts` を参照・更新し、表示のために `users`（送信者の会員情報）・`products`（対象商品）・`admins`（対応者）を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

本機能で対応状況を保持する列を追加する。

| カラム | 型 | 用途 |
|---|---|---|
| `contacts.status` | `string(20)`・default `unhandled` | 対応ステータス（`App\Enums\ContactStatus`） |
| `contacts.admin_note` | `text`・nullable | 対応メモ。管理者のみが入力・閲覧する |
| `contacts.handled_admin_id` | `foreignId`・nullable・→ `admins.id` set null | 最後に対応状況を更新した管理者 |
| `contacts.handled_at` | `timestamp`・nullable | 対応済みへ変更した日時。対応所要時間の集計に使う |

対象商品を識別する `contacts.product_id` は送信時に記録する列であり、[docs/front/contact.md](../front/contact.md) が正本。本機能ではタブの振り分けと商品詳細への導線に使う。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
一覧（GET /admin/contacts）
お問い合わせ管理                                    [CSVエクスポート]
+------------------------------------------------------------------+
| [通常のお問い合わせ 18] [商品からのお問い合わせ 6]                    |
+------------------------------------------------------------------+
| ステータス | 受信日 [開始]〜[終了] | キーワード |[クリア] 6件 / 全24件 |
+------------------------------------------------------------------+
| 受信日時         |お名前  |メールアドレス    |本文       |状態  |   |
|------------------|--------|------------------|-----------|------|---|
| 2026.08.18 10:30 |山田 太郎|taro@example.com |サイズ選び…|未対応|詳細|
| 2026.08.17 09:12 |佐藤 花子|hanako@example.com|納期につ…|対応済み|詳細|
+------------------------------------------------------------------+
                         [前へ] [1] [2] [次へ]
```

- タブは「通常のお問い合わせ」「商品からのお問い合わせ」の2つで、右肩にそれぞれの総件数（絞り込み前）を出す。既定は「通常のお問い合わせ」。
- 「商品からのお問い合わせ」タブのみ、メールアドレス列の右に「対象商品」列を表示する。
- 本文列は1行に収まる長さで切り、続きがある場合は末尾に `…` を付ける。
- キーワードは入力から400ms後に反映する。ステータス・受信日は選択と同時に反映する。タブ・絞り込み条件はクエリストリングに保持し、詳細画面から戻っても状態が維持される。
- 受信日は開始・終了それぞれ単独でも指定できる（`<input type="date">`）。キーワードの入力欄には対象（お名前・メールアドレス・対象商品・お問い合わせ内容）をプレースホルダーで示す。
- ステータスはバッジで表示する。配色は `App\Enums\ContactStatus::color()` が正本で、サーバーから `status_tone` として渡す。
- 該当0件のときは「該当するお問い合わせがありません」を表示する。
- CSVエクスポートは表示中のタブと絞り込み条件をそのまま引き継ぐ。

```
詳細（GET /admin/contacts/{contact}）
← お問い合わせ一覧へ
[未対応] 受信日時 2026.08.18 10:30

+---------------------------+  +---------------------------+
| お問い合わせ内容           |  | 送信者                     |
| サイズ選びについて相談です。|  | 山田 太郎                  |
| （改行を保って表示）        |  | taro@example.com          |
|                           |  | 会員 M-100238              |
|                           |  |---------------------------|
|                           |  | 対象商品                   |
|                           |  | チームジャージ 2026 →      |
+---------------------------+  +---------------------------+
                               | 対応状況                   |
                               | [対応中          ▼]       |
                               | 対応メモ                   |
                               | [                       ]  |
                               | [ 対応状況を更新 ]          |
                               | 最終更新 2026.08.18 11:20  |
                               |  / 管理者 佐藤             |
                               +---------------------------+
```

- 本文は入力された改行を保って表示する。Markdown 記法として解釈しない（[docs/mail-notification.md](../mail-notification.md) と同じ扱い）。
- 「送信者」の会員IDは、ログイン中に送信された問い合わせのみ表示する。未ログインからの送信は「会員登録なし」と表示する。
- 「対象商品」は `product_id` が残っている場合のみ商品名を出し、フロントの商品詳細へリンクする。手入力の商品名しか無い場合はリンクを張らずテキストのみを表示し、商品が削除済みの場合は保存された商品名をテキストで表示する。
- 更新は確認モーダル（`ConfirmDialog`）を経て実行し、完了後にトーストを表示する。
- 「最終更新」は一度でも対応状況を更新した場合のみ表示する。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/admin/Pages/Contact/Index.tsx
type ContactRow = {
    id: number;
    received_at: string;          // 'YYYY.MM.DD HH:mm'
    name: string;
    email: string;
    body_excerpt: string;         // 本文を60文字で切り、続きがあれば '…' を付けた文字列
    product_name: string | null;  // 商品タブでのみ使用
    status: string;               // ContactStatus の値
    status_label: string;
    status_tone: Tone;            // { fg, bg }
};

type Props = {
    contacts: Paginated<ContactRow>;
    filters: {
        tab: ContactTab;
        status: string;
        q: string | null;
        from: string | null;   // 'YYYY-MM-DD'
        to: string | null;     // 'YYYY-MM-DD'
    };
    tabCounts: { general: number; product: number };  // 絞り込み前の各タブの件数
    totalCount: number;                               // 表示中のタブの絞り込み前の件数
    statusOptions: { value: string; label: string }[];
};

// resources/js/admin/Pages/Contact/Show.tsx
type Props = {
    contact: {
        id: number;
        received_at: string;      // 'YYYY.MM.DD HH:mm'
        name: string;
        email: string;
        body: string;
        member_code: string | null;   // 未ログインからの送信は null
        product: { id: number; name: string } | null;  // product_id が残っている場合のみ
        product_name: string | null;  // 保存された対象商品の文字列
        status: string;
        status_label: string;
        status_tone: Tone;
        admin_note: string | null;
        handled_at: string | null;    // 'YYYY.MM.DD HH:mm'
        handled_admin_name: string | null;
    };
    statusOptions: { value: string; label: string }[];
    errors: Record<string, string>;
};
```

タブの値は `resources/js/shared/lib/enums.ts` に `ContactTab`（`general` / `product`）、ステータスの値は同ファイルに `ContactStatus` として定義し、バックエンドの `App\Enums\ContactStatus` と値を揃える。

### 対応ステータスの区分（`App\Enums\ContactStatus`）

| 値 | 表示名 | 用途 |
|---|---|---|
| `unhandled` | 未対応 | 初期値。送信された時点でこの値になる |
| `in_progress` | 対応中 | 担当者が着手済みで、返信・確認が完了していない |
| `handled` | 対応済み | 返信・対応が完了した。対応不要と判断したものもここに含める |

注文のステータスと異なり**遷移の制限は設けない**。どの区分からどの区分へも変更でき、対応済みから未対応へ戻すこともできる。

### 入力値バリデーションルール（`Admin\Contact\UpdateContactRequest`）

| 項目 | ルール |
|---|---|
| `status` | 必須・`App\Enums\ContactStatus` の値のいずれか |
| `admin_note` | 任意・文字列・最大2000 |

### 主要な処理フロー

**一覧（`index()` と `BuildContactFilter`）**

1. タブを適用する。`product` は `product_id` が `null` でない行、`general` は `null` の行。`tab` が未指定・不正な値のときは `general` に丸める。
2. 絞り込みを適用する。
   - `status`: 完全一致（`all` は条件なし）。`App\Enums\ContactStatus` の値の一覧と照合し、外れた値は `all` に丸める
   - `q`: `name` / `email` / `product_name` / `body` のいずれかへの部分一致。`%` と `_` はエスケープする
   - `from` / `to`: `created_at` の期間。開始・終了は片方だけでも指定できる
3. `created_at` の降順（同時刻は `id` の降順）で1ページ50件のページネーションを行い、クエリストリングを引き継ぐ。
4. タブごとの総件数（`tabCounts`）は絞り込みを適用せずに数える。

**詳細（`show()`）**

`contacts` の1件と、`user`（会員IDの表示）・`product`（商品詳細への導線）・`handledAdmin`（対応者名）を eager load して返す。

**対応状況の更新（`UpdateContactHandlingService`）**

1. `status` と `admin_note` を保存する。
2. `handled_admin_id` に操作した管理者のIDを設定する。
3. `handled` へ変更した場合は `handled_at` に現在時刻を設定する。`handled` 以外へ変更した場合は `handled_at` を `null` に戻す。同じ区分のまま対応メモだけを更新した場合は `handled_at` を変更しない。
4. 詳細画面へ戻して `対応状況を更新しました。` を表示する。

### 絞り込みクエリの実装方針

- 受信日は境界を明示した範囲比較で行う。開始日は `created_at >= 開始日 00:00:00`、終了日は `created_at < 終了日の翌日 00:00:00` とする。`DATE(created_at)` の形に変換すると `index(status, created_at)` が使えなくなるため、日付関数を条件の左辺に置かない。終了日に指定した日の当日分は結果に含める。
- 日付として解釈できない `from` / `to` は条件なしとして扱う。開始日が終了日より後の場合は条件をそのまま適用し、結果は0件になる。
- `q` の4列への `OR` は1つの括弧でまとめ、タブ・ステータス・受信日の条件と `AND` で結合する。括弧で囲まないと `OR` が他の条件を飲み込む。
- キーワードは中間一致（`%語%`）のため索引が効かず、`body` は `text` 型で索引の対象にもならない。件数が増えて検索が遅くなった場合は全文検索索引（`FULLTEXT`）の導入を別途検討する。本改修では導入しない。

**CSVエクスポート（`ContactCsvExporter`）**

表示中のタブと絞り込み条件を一覧と同じ規則で適用し、`created_at` の降順で書き出す。文字コード・改行コードの扱いは [docs/admin/csv.md](csv.md) が正本。ファイル名は `contacts_YYYYMMDD.csv`。

| 列 | 項目 | 内容 |
|---|---|---|
| A | 受信日時 | `YYYY-MM-DD HH:MM:SS` |
| B | 種別 | `商品` / `通常`（`product_id` の有無） |
| C | お名前 | `name` |
| D | メールアドレス | `email` |
| E | 会員ID | `users.member_code`。未ログインからの送信・会員削除済みは空 |
| F | 対象商品 | `product_name` |
| G | 商品ID | `products.product_code`。`product_id` が無い・商品削除済みは空 |
| H | お問い合わせ内容 | `body`。改行を含む |
| I | ステータス | `未対応` / `対応中` / `対応済み` |
| J | 対応メモ | `admin_note` |
| K | 対応者 | `admins.name`。未更新は空 |
| L | 対応日時 | `handled_at` を `YYYY-MM-DD HH:MM:SS`。未対応・対応中は空 |

インポートは提供しない。

## 業務ルール

- 問い合わせの編集・削除は実装しない。送信者が入力した内容（お名前・メールアドレス・対象商品・本文）は管理画面から書き換えられない。変更できるのは対応ステータスと対応メモのみ。
- 管理画面からの返信メール送信は実装しない。返信は管理者のメールソフトから行い、対応の記録は対応メモに残す。
- 対応者は最後に対応状況を更新した管理者で上書きする。誰がいつどの区分へ変更したかの履歴は保持しない。
- タブの振り分けは `product_id` の有無のみで決める。商品詳細を経由せず対象商品を手入力した問い合わせは `product_id` を持たないため「通常のお問い合わせ」に入る。
- 対応メモは管理者専用の記録であり、送信者へは一切表示・送信しない。

## 関連ドキュメント

- [docs/front/contact.md](../front/contact.md) — お問い合わせフォームの正本。`product_id` / `product_name` の記録規則もこちら
- [docs/admin/csv.md](csv.md) — CSVの文字コード・改行コードの正本
- [docs/admin/dashboard.md](dashboard.md) — 未対応件数のサマリー表示
- [docs/admin/common-layout.md](common-layout.md) — `AdminLayout`・`FilterBar`・`DataTable`・`Pagination`・`ConfirmDialog` の正本。サイドメニューへの「お問い合わせ管理」の追加もこちらが正本
- [docs/2_database.md](../2_database.md) — `contacts` の列定義・`ContactStatus`・`Contact` モデル・`ContactFactory` の正本
- [docs/mail-notification.md](../mail-notification.md) — 受付通知・控えメールの正本

## ソースファイル

| 種別 | パス |
|---|---|
| Migration | `database/migrations/2026_08_18_000001_add_product_and_handling_columns_to_contacts_table.php` |
| Enum | `app/Enums/ContactStatus.php` |
| Model | `app/Models/Contact.php` |
| Controller | `app/Http/Controllers/Admin/ContactController.php` |
| Controller | `app/Http/Controllers/Admin/ContactCsvController.php` |
| FormRequest | `app/Http/Requests/Admin/Contact/UpdateContactRequest.php` |
| Action | `app/Actions/Admin/Contact/BuildContactFilter.php` |
| Service | `app/Services/Admin/Contact/UpdateContactHandlingService.php` |
| Service | `app/Services/Admin/Csv/ContactCsvExporter.php` |
| Service | `app/Services/Admin/Csv/CsvWriter.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Contact/Index.tsx` |
| Page | `resources/js/admin/Pages/Contact/Show.tsx` |
| Component | `resources/js/admin/Components/Contact/StatusBadge.tsx` |
| Component | `resources/js/admin/Components/Contact/TabNav.tsx` |
| Component | `resources/js/admin/Components/Contact/HandlingCard.tsx` |
| 型定義 | `resources/js/shared/lib/enums.ts` |
| Test | `tests/Feature/Admin/Contact/ContactIndexTest.php` |
| Test | `tests/Feature/Admin/Contact/ContactShowTest.php` |
| Test | `tests/Feature/Admin/Contact/ContactUpdateTest.php` |
| Test | `tests/Feature/Admin/Csv/ContactCsvExportTest.php` |

## 受け入れ条件

本機能を担保する自動テストは未作成。以下のうち「Featureテスト」と記した項目は実装時に上表のテストファイルで担保し、「目視確認」と記した項目は人手で確認する。

**一覧**

- ログイン済みの管理者が一覧を表示でき、未認証はログイン画面へリダイレクトされる: Featureテスト
- 既定で「通常のお問い合わせ」タブが選ばれ、`product_id` を持たない問い合わせのみが表示される: Featureテスト
- 「商品からのお問い合わせ」タブでは `product_id` を持つ問い合わせのみが表示される: Featureテスト
- 対象商品を手入力しただけの問い合わせが「通常のお問い合わせ」タブに入る: Featureテスト
- 不正な `tab` の値が「通常のお問い合わせ」に丸められる: Featureテスト
- タブごとの総件数が絞り込みの影響を受けない: Featureテスト
- ステータスで絞り込める・不正なステータス値が `all` に丸められる: Featureテスト
- お名前・メールアドレス・対象商品・お問い合わせ内容のいずれかへの部分一致で絞り込める: Featureテスト
- 絞り込みの検索語に `%` や `_` を含めても部分一致として扱われない: Featureテスト
- キーワードとステータスを同時に指定したとき、キーワードの `OR` 条件がステータスの条件を飲み込まない: Featureテスト
- 受信日の開始・終了で絞り込め、開始日当日と終了日当日の問い合わせがどちらも結果に含まれる: Featureテスト
- 受信日を開始のみ・終了のみでも絞り込める: Featureテスト
- 日付として解釈できない受信日は条件なしとして扱われる: Featureテスト
- 開始日が終了日より後のときは0件になる: Featureテスト
- タブと各絞り込みを組み合わせられる: Featureテスト
- 一覧が受信日時の降順で並ぶ: Featureテスト
- 1ページに50件を超える場合にページネーションされる: Featureテスト
- 該当0件で空の一覧になる: Featureテスト
- 本文が60文字で切られ、続きがある場合のみ `…` が付く: Featureテスト
- タブ・絞り込み条件がクエリストリングに保持され、詳細から戻っても維持される: 目視確認
- キーワードのデバウンス（400ms）、ステータス・受信日の即時反映、クリアボタン: 目視確認
- ステータスバッジの配色・タブの件数表示: 目視確認

**詳細**

- 詳細が表示され、未認証は開けない: Featureテスト
- 本文・お名前・メールアドレスが送信された内容のまま表示される: Featureテスト
- ログイン中に送信された問い合わせで会員IDが表示され、未ログインからの送信では `null` が渡る: Featureテスト
- `product_id` を持つ問い合わせで商品IDと商品名が渡る: Featureテスト
- 商品が削除された問い合わせでも保存された商品名が表示される: Featureテスト
- 会員が削除された問い合わせでも送信内容が表示される: Featureテスト
- 本文の改行が保たれ、Markdown 記法が装飾に変換されない: 目視確認
- 商品詳細へのリンクが機能する: 目視確認

**対応状況の更新**

- ステータスと対応メモを更新でき、未認証は更新できない: Featureテスト
- 更新した管理者が `handled_admin_id` に記録される: Featureテスト
- 対応済みへの変更で `handled_at` が記録される: Featureテスト
- 対応済みから他の区分へ戻すと `handled_at` が `null` に戻る: Featureテスト
- 同じ区分のまま対応メモだけを更新しても `handled_at` が変わらない: Featureテスト
- どの区分からどの区分へも変更できる（遷移の制限がない）: Featureテスト
- 不正なステータス値・上限を超える対応メモが拒否される: Featureテスト
- 送信者が入力した内容が更新の対象にならない: Featureテスト
- 確認モーダルとトーストの表示: 目視確認

**CSVエクスポート**

- 表示中のタブと絞り込み条件が反映される: Featureテスト
- 全12列が定義どおりの順序・内容で出力される: Featureテスト
- 種別列が `product_id` の有無で `商品` / `通常` に出し分けられる: Featureテスト
- 未ログインからの送信で会員ID列が空になる: Featureテスト
- 商品が削除された行で商品ID列が空になり、商品名は残る: Featureテスト
- 未対応・対応中の行で対応日時列が空になる: Featureテスト
- 先頭にバイトオーダーマークが付き、改行コードが復帰改行になる: Featureテスト
- 受信日時の降順で書き出される: Featureテスト
- ダウンロードしたCSVをExcelで開いて文字化けしないこと: 目視確認
