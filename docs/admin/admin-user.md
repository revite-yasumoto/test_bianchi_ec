# 管理者マスタ

## 機能概要

- **対象画面・機能の目的:** 管理画面にログインできる管理者アカウントの一覧・登録・編集・削除。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/admins` | `admin.admins.index` | `auth:admin` |
| GET | `/admin/admins/create` | `admin.admins.create` | `auth:admin` |
| POST | `/admin/admins` | `admin.admins.store` | `auth:admin` |
| GET | `/admin/admins/{admin}/edit` | `admin.admins.edit` | `auth:admin` |
| PUT | `/admin/admins/{admin}` | `admin.admins.update` | `auth:admin` |
| DELETE | `/admin/admins/{admin}` | `admin.admins.destroy` | `auth:admin` |

`Route::resource()` の既定パラメータ名は `admin` だが、ルートグループの名前空間と混同しないよう `parameters()` で明示する。

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けず、登録された管理者はすべての機能を利用できる。
- **本ドキュメントのスコープ:** 管理者アカウントのCRUD。ログイン処理は [docs/admin/auth.md](auth.md) が正本。CSVインポート／エクスポートは単位09。

## 使用テーブル

`admins` を使用する。削除時に `order_status_histories.admin_id` が `null` になる（`ON DELETE SET NULL`）。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
一覧（GET /admin/admins）                      [＋ 管理者登録]
管理者ごとの権限管理は行いません。登録された管理者はすべての機能を利用できます。
+------------------------------------------------------------------+
| 管理者ID | 氏名              | メールアドレス      | 登録日   |      |
|----------|-------------------|---------------------|----------|------|
| A-001    | 管理 太郎（ログイン中）| admin@bianchi.demo | 2026.07.01|編集 削除|
| A-002    | 運用 花子          | ops@bianchi.demo   | 2026.07.02|編集 削除|
+------------------------------------------------------------------+

登録・編集（最大幅560px）
+---------------------------+
| 管理者ID A-002（変更できません）  ※編集時のみ
| 氏名        [            ] |
| メールアドレス [          ] |
| パスワード（変更する場合のみ入力）
| パスワード（確認）          |
+---------------------------+
| [ 保存する ] [キャンセル]  |
```

- ログイン中の管理者の行には「（ログイン中）」を表示する。
- 削除は確認モーダル（`ConfirmDialog`）を経て実行し、完了後にトーストを表示する。拒否された場合はその理由をトーストで表示する。
- 管理者IDは登録時に自動採番し、編集画面では変更できない旨を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type AdminUserRow = {
    id: number;
    admin_code: string;
    name: string;
    email: string;
    registered_on: string;   // 'YYYY.MM.DD'
};

type IndexProps = { admins: AdminUserRow[] };

type FormProps = {
    admin: { id: number; admin_code: string; name: string; email: string } | null;  // null = 新規登録
};
```

一覧・フォームともパスワードハッシュは渡さない。

- **入力値バリデーションルール（`Admin\AdminUser\StoreAdminUserRequest`）:**

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `email` | 必須・文字列・メール形式・最大191・`admins.email` で一意 |
| `password` | 必須・`password_confirmation` と一致・8文字以上 |

- **入力値バリデーションルール（`Admin\AdminUser\UpdateAdminUserRequest`）:** 上記と同じだが `email` の一意チェックで自身を除外し、`password` は任意（未入力なら変更しない）。

- **主要な処理フロー:**

**一覧（`index()`）:** `admin_code` の昇順で全件を返す（ページネーションなし）。

**登録（`store()`）:** `GenerateAdminCode` で管理者IDを採番し、パスワードをハッシュして作成する。

**更新（`update()`）:** 氏名・メールアドレスを更新する。パスワード欄が空なら既存のハッシュを保持し、入力があれば再ハッシュする。

**削除（`destroy()`）:**
1. ログイン中の自分自身なら「ログイン中の自分自身は削除できません。」を `delete` のエラーとして返す。
2. 管理者が1名以下なら「管理者が1名のため削除できません。」を返す。
3. いずれにも該当しなければ削除する。

### 管理者IDの採番（`GenerateAdminCode`）

- `A-` で始まる `admin_code` のうち最大のものを取り、数値部分 + 1 を `A-` + 3桁ゼロ埋めで組み立てる。該当が無ければ `A-001`。
- 採番対象を `A-` で始まるものに絞るのは、ログインIDとして使う `A-` 形式でない管理者コード（デモ用アカウント等）が混在しうるため。文字列としての最大値を単純に取ると、そうしたコードが選ばれて採番が破綻する。

## 業務ルール

- 管理者ごとの権限管理は実装しない（要件で対象外）。全管理者が全機能を利用できる。
- 管理者は自分自身を削除できない。また最後の1名も削除できない（管理画面に誰も入れなくなる状態を防ぐ）。
- 管理者を削除しても注文ステータスの変更履歴は残る。履歴の `admin_id` が `null` になり、画面上は操作者名が失われる。

## 関連ドキュメント

- [docs/admin/csv.md](csv.md) — 管理者CSVの取り込み・書き出しの正本
- [docs/admin/auth.md](auth.md) — 管理者ログインの正本。`admin_code` またはメールアドレスで認証する
- [docs/admin/order-show.md](order-show.md) — 管理者を参照するステータス変更履歴
- [docs/admin/common-layout.md](common-layout.md) — `DataTable`・`ConfirmDialog` の正本
- [docs/2_database.md](../2_database.md) — `admins` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/AdminUserController.php` |
| FormRequest | `app/Http/Requests/Admin/AdminUser/StoreAdminUserRequest.php` |
| FormRequest | `app/Http/Requests/Admin/AdminUser/UpdateAdminUserRequest.php` |
| Action | `app/Actions/Admin/AdminUser/GenerateAdminCode.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/AdminUser/Index.tsx` |
| Page | `resources/js/admin/Pages/AdminUser/Form.tsx` |
| Component | `resources/js/admin/Components/AdminUser/AdminUserTable.tsx` |
| Test | `tests/Feature/Admin/AdminUser/AdminUserStoreTest.php` |
| Test | `tests/Feature/Admin/AdminUser/AdminUserUpdateTest.php` |
| Test | `tests/Feature/Admin/AdminUser/AdminUserDestroyTest.php` |

## 受け入れ条件

- 登録画面が表示される: `tests/Feature/Admin/AdminUser/AdminUserStoreTest.php`（`管理者登録画面が表示される`）
- 管理者を登録できる: 同上（`管理者を登録できる`）
- 管理者IDが既存の最大連番 + 1 で採番される: 同上（`管理者番号は既存の最大連番の次で採番される`）
- `A-` 形式でないコードが採番に影響しない: 同上（`接頭辞のない管理者番号は採番の対象にならない`）
- 登録した管理者でログインできる: 同上（`登録した管理者でログインできる`）
- メール重複・短いパスワード・確認不一致・氏名未入力を拒否する: 同上（`重複したメールアドレスでは登録できない`・`パスワードが8文字未満では登録できない`・`パスワードが確認用と一致しないと登録できない`・`氏名が未入力では登録できない`）
- 未認証は登録できない: 同上（`未認証は管理者を登録できない`）
- 編集画面に既存の値が渡る: `tests/Feature/Admin/AdminUser/AdminUserUpdateTest.php`（`管理者編集画面に既存の値が渡される`）
- 氏名・メールアドレスを更新できる: 同上（`氏名とメールアドレスを更新できる`）
- パスワード未入力で既存が保持され、入力すると変更される: 同上（`パスワード未入力なら既存のパスワードが保持される`・`パスワードを入力すると変更される`）
- 自身のメールは重複扱いにならず、他管理者との重複は弾かれる: 同上（`自分自身のメールアドレスは重複として扱われない`・`他の管理者と同じメールアドレスには変更できない`）
- 短いパスワードへの変更を拒否する: 同上（`短すぎるパスワードには変更できない`）
- 未認証は更新できない: 同上（`未認証は管理者を更新できない`）
- 他の管理者を削除できる: `tests/Feature/Admin/AdminUser/AdminUserDestroyTest.php`（`他の管理者を削除できる`）
- 自分自身・最後の1名を削除できない: 同上（`ログイン中の自分自身は削除できない`・`管理者が1名のときは削除できない`）
- 削除後も変更履歴が残り `admin_id` が `null` になる: 同上（`管理者を削除しても変更履歴は残り管理者への参照だけが外れる`）
- 未認証は削除できない: 同上（`未認証は管理者を削除できない`）
- 削除確認モーダルとトースト表示: 自動テストなし。目視確認で担保する
- ログイン中の管理者の行に「（ログイン中）」が表示される: 自動テストなし。目視確認で担保する
