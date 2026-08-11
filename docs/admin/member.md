# 会員マスタ

## 機能概要

- **対象画面・機能の目的:** 会員の一覧・検索と、会員詳細の確認・ステータス（有効／休会）の更新。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/admin/members` | `admin.members.index` | `auth:admin` |
| GET | `/admin/members/{user}` | `admin.members.show` | `auth:admin` |
| PUT | `/admin/members/{user}/status` | `admin.members.status.update` | `auth:admin` |

- **アクセス権限・ミドルウェア:** 上表の通り。管理者ごとの権限区分は設けない（[docs/1_system_overview.md](../1_system_overview.md) 参照）。
- **本ドキュメントのスコープ:** 会員の一覧・検索・詳細・ステータス更新。会員情報そのものの編集は管理画面では行わない。CSVインポート／エクスポートは単位09。

## 使用テーブル

`users` を主体に `user_addresses`（＋`prefectures`）と `orders` を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
一覧（GET /admin/members）
+------------------------------------------------------------------+
| 氏名・メール・会員ID                     |[クリア]   6件 / 全24件  |
+------------------------------------------------------------------+
| 会員ID   | 氏名     | メールアドレス      | 登録日     |ステータス|    |
|----------|----------|---------------------|------------|---------|----|
| M-100238 | 山田 太郎| taro@example.com    | 2026.07.24 | 有効    |詳細|
| M-100412 | 田中 美咲| misaki@example.com  | 2026.06.15 | 休会    |詳細|
+------------------------------------------------------------------+

詳細（GET /admin/members/{user}） 2カラム
+---------------------------+  +---------------------------+
| 会員情報          [有効]  |  | ステータス更新             |
| 会員ID / 氏名 / メール    |  | 現在は「有効」です…        |
| 電話番号 / 登録日         |  | [「休会」に変更する]        |
| 会員情報の編集は…         |  +---------------------------+
+---------------------------+  | 直近の注文                 |
| 配送先住所                |  | BNC-2607-0918 …  ¥18,000  |
| 自宅 [既定]               |  +---------------------------+
+---------------------------+
```

- 検索は入力から400ms後に反映し、条件はクエリストリングに保持する。
- ステータスはバッジで表示する（有効＝緑系、休会＝赤系）。
- ステータス更新は確認モーダル（`ConfirmDialog`）を経て実行し、完了後にトーストを表示する。
- 該当0件のときは「該当する会員がいません」を表示する。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
// 一覧
type MemberRow = {
    id: number;
    member_code: string;
    name: string;
    email: string;
    registered_on: string;   // 'YYYY.MM.DD'（created_at）
    status: string;          // 'active' | 'suspended'
    status_label: string;
};

type IndexProps = {
    members: Paginated<MemberRow>;
    filters: { q: string | null };
    totalCount: number;
};

// 詳細
type ShowProps = {
    member: {
        id: number; member_code: string; name: string; name_kana: string | null;
        email: string; tel: string | null; registered_on: string;
        status: string; status_label: string;
    };
    addresses: {
        id: number; label: string; recipient_name: string; postal_code: string;
        prefecture_name: string; city: string; address_line1: string;
        address_line2: string | null; tel: string; is_default: boolean;
    }[];
    recentOrders: { id: number; order_number: string; ordered_at: string; total: number; status_label: string }[];
};
```

会員に渡すのは画面が表示する項目のみで、パスワードハッシュ・`remember_token` は含めない。

- **入力値バリデーションルール（`Admin\Member\UpdateMemberStatusRequest`）:**

| 項目 | ルール |
|---|---|
| `status` | 必須・`App\Enums\UserStatus` の値（`active` / `suspended`）のいずれか |

- **主要な処理フロー:**

**一覧（`index()`）:** `q` があれば `name` / `email` / `member_code` のいずれかに部分一致する会員を絞り込む（`%` と `_` はエスケープする）。`created_at` の降順（同時刻は `id` の降順）で1ページ50件のページネーションを行う。

**詳細（`show()`）:** 会員情報に加え、配送先住所（都道府県名は `prefectures` から解決）と直近10件の注文を渡す。注文は `ordered_at` の降順。

**ステータス更新（`update()`）:** `users.status` を更新して直前の画面へ戻る。

## 業務ルール

- 会員の削除・退会は実装しない。利用停止は `status = suspended`（休会）で表す。
- 休会にした会員はフロントにログインできなくなる（判定は [docs/front/auth.md](../front/auth.md) が正本）。
- 会員情報（氏名・メールアドレス等）の編集は管理画面から行わない。会員自身がマイページで変更する。

## 関連ドキュメント

- [docs/front/auth.md](../front/auth.md) — 休会会員のログイン拒否の正本
- [docs/admin/order-show.md](order-show.md) — 直近注文からリンクする注文詳細
- [docs/admin/common-layout.md](common-layout.md) — `FilterBar`・`DataTable`・`Pagination`・`ConfirmDialog` の正本
- [docs/2_database.md](../2_database.md) — `users`・`user_addresses` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Admin/MemberController.php` |
| Controller | `app/Http/Controllers/Admin/MemberStatusController.php` |
| FormRequest | `app/Http/Requests/Admin/Member/UpdateMemberStatusRequest.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/admin/Pages/Member/Index.tsx` |
| Page | `resources/js/admin/Pages/Member/Show.tsx` |
| Component | `resources/js/admin/Components/Member/MemberStatusCard.tsx` |
| 区分値 | `resources/js/shared/lib/enums.ts` |
| Test | `tests/Feature/Admin/Member/MemberIndexTest.php` |
| Test | `tests/Feature/Admin/Member/MemberShowTest.php` |
| Test | `tests/Feature/Admin/Member/MemberStatusUpdateTest.php` |

## 受け入れ条件

- 一覧が表示され、登録日が `YYYY.MM.DD` 形式になる: `tests/Feature/Admin/Member/MemberIndexTest.php`（`会員一覧が表示される`）
- 氏名・メール・会員IDで絞り込める: 同上（`氏名で絞り込める`・`メールアドレスで絞り込める`・`会員番号で絞り込める`）
- 休会中の会員がステータス付きで表示される: 同上（`休会中の会員もステータス付きで一覧に出る`）
- 一覧が登録日の降順で並ぶ: 同上（`一覧は登録日の降順で並ぶ`）
- 該当0件で空の一覧になる: 同上（`該当がない場合は空の一覧になる`）
- 一覧にパスワードハッシュが含まれない: 同上（`一覧にパスワードハッシュが含まれない`）
- 未認証は一覧を開けない: 同上（`未認証はログイン画面へリダイレクトされる`）
- 詳細に会員情報が表示される: `tests/Feature/Admin/Member/MemberShowTest.php`（`会員詳細が表示される`）
- 配送先住所が都道府県名付きで表示される: 同上（`配送先住所が都道府県名付きで表示される`）
- 直近注文が新しい順で表示され、他会員の注文が混ざらない: 同上（`直近の注文が新しい順で表示される`・`他の会員の注文は表示されない`）
- 詳細にパスワードハッシュが含まれない: 同上（`会員詳細にパスワードハッシュが含まれない`）
- 未認証は詳細を開けない: 同上（`未認証は会員詳細を開けない`）
- 休会・有効を相互に切り替えられる: `tests/Feature/Admin/Member/MemberStatusUpdateTest.php`（`会員を休会にできる`・`休会中の会員を有効に戻せる`）
- 休会にした会員がフロントでログインできなくなる: 同上（`休会にした会員はフロントでログインできなくなる`）
- 不正なステータス値を拒否する: 同上（`不正なステータス値では更新できない`）
- 未認証はステータスを更新できない: 同上（`未認証は会員ステータスを更新できない`）
- 検索のデバウンス・クエリストリングでの保持: 自動テストなし。目視確認で担保する
- ステータス更新の確認モーダルとトースト: 自動テストなし。目視確認で担保する
