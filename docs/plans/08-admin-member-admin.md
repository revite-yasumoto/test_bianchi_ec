# 単位08: 管理画面 - 会員マスタ・管理者マスタ

依存: 単位03

## スコープ

- 会員マスタ: 一覧（会員ID・氏名・メール・登録日・ステータス）・検索（氏名／メール／会員ID）・クリアボタン・件数表示・詳細
- 管理者マスタ: 一覧（管理者ID・氏名・メール・登録日）・新規登録・編集・削除
- CSVインポート／エクスポートは単位09

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
| GET | `/admin/members` | `admin.members.index` | 会員一覧 |
| GET | `/admin/members/{user}` | `admin.members.show` | 会員詳細 |
| PUT | `/admin/members/{user}/status` | `admin.members.status.update` | 会員ステータス更新（有効／休会） |
| GET | `/admin/admins` | `admin.admins.index` | 管理者一覧 |
| GET | `/admin/admins/create` | `admin.admins.create` | 管理者登録フォーム |
| POST | `/admin/admins` | `admin.admins.store` | 管理者登録 |
| GET | `/admin/admins/{admin}/edit` | `admin.admins.edit` | 管理者編集フォーム |
| PUT | `/admin/admins/{admin}` | `admin.admins.update` | 管理者更新 |
| DELETE | `/admin/admins/{admin}` | `admin.admins.destroy` | 管理者削除 |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/MemberController.php` | `index()` / `show()` |
| Controller | `app/Http/Controllers/Admin/MemberStatusController.php` | `update()` |
| Controller | `app/Http/Controllers/Admin/AdminUserController.php` | `index()` / `create()` / `store()` / `edit()` / `update()` / `destroy()` |
| FormRequest | `app/Http/Requests/Admin/Member/UpdateMemberStatusRequest.php` | ステータス値の検証 |
| FormRequest | `app/Http/Requests/Admin/AdminUser/StoreAdminUserRequest.php` | 管理者登録のバリデーション |
| FormRequest | `app/Http/Requests/Admin/AdminUser/UpdateAdminUserRequest.php` | 管理者更新のバリデーション（メール一意で自身を除外・パスワードは任意） |
| Action | `app/Actions/Admin/AdminUser/GenerateAdminCode.php` | 管理者ID（`A-` + 3桁連番）の採番 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Member/Index.tsx` | 検索バー＋一覧テーブル |
| Page | `resources/js/admin/Pages/Member/Show.tsx` | 会員情報・配送先住所一覧・注文履歴（直近）・ステータス更新 |
| Page | `resources/js/admin/Pages/AdminUser/Index.tsx` | 一覧テーブル＋「権限管理は対象外」の注記 |
| Page | `resources/js/admin/Pages/AdminUser/Form.tsx` | 登録／編集の共用フォーム |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「会員マスタ」「管理者マスタ」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
// Member/Index.tsx
type MemberRow = {
  id: number;
  member_code: string;
  name: string;
  email: string;
  registered_on: string;     // 'YYYY.MM.DD'（created_at）
  status: 'active' | 'suspended';
  status_label: string;      // '有効' / '休会'
};
type Props = { members: Paginated<MemberRow>; filters: { q: string | null }; totalCount: number };

// Member/Show.tsx
type Props = {
  member: {
    member_code: string; name: string; name_kana: string | null;
    email: string; tel: string | null; registered_on: string;
    status: 'active' | 'suspended';
  };
  addresses: { label: string; recipient_name: string; postal_code: string; prefecture_name: string; city: string; address_line1: string; address_line2: string | null; tel: string; is_default: boolean }[];
  recentOrders: { id: number; order_number: string; ordered_at: string; total: number; status_label: string }[];
};

// AdminUser/Form.tsx
type Props = { admin: { id: number; admin_code: string; name: string; email: string } | null };
```

### バリデーション

`StoreAdminUserRequest`:

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `email` | 必須・メール形式・最大191・`admins.email` で一意 |
| `password` | 必須・確認一致・8文字以上・`Password::defaults()` |

`UpdateAdminUserRequest`: 上記と同じだが `email` の一意チェックで自身を除外し、`password` は任意（未入力なら変更しない）。

`UpdateMemberStatusRequest`:

| 項目 | ルール |
|---|---|
| `status` | 必須・`App\Enums\UserStatus` の値のいずれか |

### 主要な処理フロー

**会員一覧**:
1. `users` を取得する
2. `q` が指定されていれば `name` LIKE 部分一致 **OR** `email` LIKE 部分一致 **OR** `member_code` LIKE 部分一致
3. `created_at` の降順でページネーション（1ページ50件）
4. 件数表示は「絞り込み後の件数 / 全N件」

**会員ステータス更新**: 確認モーダル → `users.status` を更新 → 「会員ステータスを更新しました」をフラッシュ。休会に変更した会員はログインできなくなる（単位02の判定）。

**管理者登録**: バリデーション → 管理者ID採番（`A-` + `admins` の最大連番 + 1、3桁ゼロ埋め）→ パスワードをハッシュして作成 → 一覧へリダイレクト

**管理者更新**: パスワード欄が空なら既存のハッシュを保持する。それ以外は再ハッシュする。

**管理者削除**:
1. 確認モーダルで確認する
2. **ログイン中の自分自身は削除できない**（自分の管理者権限を奪う操作を防ぐ）
3. **管理者が1件になる場合は削除できない**（全管理者が消えて誰も管理画面に入れなくなる状態を防ぐ）
4. `order_status_histories.admin_id` は `ON DELETE SET NULL` のため、履歴は残り管理者名だけが失われる
5. 削除 → 一覧へリダイレクト

## 業務ルール

- 管理者ごとの権限管理は実装しない（要件で対象外）。全管理者が全機能を利用できる
- 会員の削除は実装しない。利用停止は `status = suspended`（休会）で表す
- 管理者は自分自身を削除できない。また最後の1名は削除できない

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Member/MemberIndexTest.php` | 氏名・メール・会員IDの各検索が効くこと／部分一致／件数表示／登録日の降順 |
| `tests/Feature/Admin/Member/MemberShowTest.php` | 会員情報・配送先住所・直近注文が表示されること／パスワードハッシュが Props に含まれないこと |
| `tests/Feature/Admin/Member/MemberStatusUpdateTest.php` | 休会に変更できること／休会会員がフロントでログインできなくなること |
| `tests/Feature/Admin/AdminUser/AdminUserStoreTest.php` | 正常登録（管理者IDの採番形式を含む）／メール重複／パスワード8文字未満／登録した管理者でログインできること |
| `tests/Feature/Admin/AdminUser/AdminUserUpdateTest.php` | パスワード未入力時に既存パスワードが保持されること／パスワード入力時に変更されること／メール一意で自身が除外されること |
| `tests/Feature/Admin/AdminUser/AdminUserDestroyTest.php` | 他の管理者を削除できること／自分自身を削除できないこと／最後の1名を削除できないこと／削除後も `order_status_histories` が残り `admin_id` が `null` になること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **会員詳細画面の内容**: 要件は一覧の「詳細」ボタンのみを定めており、詳細画面の項目は未定義。推奨=会員情報・配送先住所一覧・直近の注文履歴（10件）・ステータス更新を表示する。会員情報の編集は管理画面からは行わない（会員自身がマイページで行う）。
2. **会員ステータスの変更**: モックには一覧にステータスバッジがあるが更新UIはない。推奨=**会員詳細から更新できるようにする**（休会状態を作れないと `status` カラムが使われないため）。
3. **管理者の自己削除・最後の1名の削除**: 要件に記載はないが、管理画面に誰も入れなくなる事故を防ぐため推奨=**いずれも拒否する**。
4. **管理者の権限管理**: 実装しない（要件で明示的に対象外）。
5. **会員の削除・退会**: 実装しない。
6. **ページネーション**: 推奨=1ページ50件。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/member.md`
  - `docs/admin/admin-user.md`
- 本計画ファイルを削除し、トラッカーの状態を更新する
