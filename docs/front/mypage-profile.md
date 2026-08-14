# フロント マイページ 会員情報変更・パスワード変更

## 機能概要

- **対象画面・機能の目的:** 会員が自分の登録情報（氏名・カナ・メールアドレス・電話番号）とパスワードを変更する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/mypage/profile` | `mypage.profile` | 会員情報変更フォーム |
| PUT | `/mypage/profile` | `mypage.profile.update` | 会員情報の更新 |
| GET | `/mypage/password` | `mypage.password` | パスワード変更フォーム |
| PUT | `/mypage/password` | `mypage.password.update` | パスワードの更新 |

- **アクセス権限・ミドルウェア:** `auth`（`web` ガード）。操作対象は常にログイン中の会員自身であり、他会員のIDを指定する経路を持たない。
- **本ドキュメントのスコープ:** 会員情報変更・パスワード変更の2タブ。マイページ共通レイアウトは [docs/front/mypage-order.md](mypage-order.md) が正本。会員登録時の入力規則は [docs/front/auth.md](auth.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `users` | 氏名・カナ・メールアドレス・電話番号・パスワードを更新する |

`member_code` / `status` は本画面から変更できない。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
会員情報変更                              パスワード変更
┌────────────────────┐              ┌────────────────────┐
│ お名前 *            │              │ 現在のパスワード *   │
│ お名前（カナ）       │              │ 新しいパスワード *   │
│ メールアドレス *     │              │ 新しいパスワード（確認）* │
│ 電話番号            │              │ [ パスワードを変更する ] │
│ [ 保存する ]        │              └────────────────────┘
└────────────────────┘
```

- 入力欄は最大幅を持たせた1カラムで並べる。必須項目にはラベル横に `*` を出す。
- エラーは各入力欄の下に出す（共通の `FormField` の枠組み）。
- 保存後はその場に留まり（`preserveScroll`）、成功メッセージをトーストで出す。パスワード変更は成功時に入力欄を空に戻す。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/MyPage/Profile.tsx
type Props = {
    profile: {
        name: string;
        name_kana: string | null;
        email: string;
        tel: string | null;
    };
};

// resources/js/front/Pages/MyPage/Password.tsx
// props なし。送信項目は current_password / password / password_confirmation
```

### 入力値バリデーションルール

**`Front\MyPage\UpdateProfileRequest`**

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `name_kana` | 任意・文字列・最大100・全角カタカナ（長音符・スペースを含む） |
| `email` | 必須・メール形式・最大191・`users.email` で一意（自分自身は除外） |
| `tel` | 任意・文字列・`[\d-]{10,20}` 形式 |

**`Front\MyPage\UpdatePasswordRequest`**

| 項目 | ルール |
|---|---|
| `current_password` | 必須・`web` ガードの現在のパスワードと一致すること |
| `password` | 必須・確認用と一致・8文字以上・現在のパスワードと異なること |

### 主要な処理フロー

**会員情報の更新**

1. 入力を検証する。メールアドレスの一意判定からは自分自身を除外する。
2. ログイン中の会員の該当列を更新する。
3. 元の画面へ戻して `会員情報を更新しました` を表示する。

**パスワードの更新**

1. 現在のパスワードを照合する。誤っていれば `現在のパスワードが正しくありません。` を返す。
2. 新しいパスワードをハッシュして保存する（モデルの `hashed` キャスト）。
3. 元の画面へ戻して `パスワードを変更しました` を表示する。

## 業務ルール

- メールアドレスを変更しても再認証・確認メールは求めない（本サイトはメール認証を使わない）。
- パスワード変更後もログイン状態を維持し、他端末のセッションも切断しない。
- 会員の退会（アカウント削除）は本画面では扱わない（スコープ外）。
- 会員情報を変更しても、確定済みの注文が保持する注文者情報は変わらない（[docs/order-snapshot.md](../order-snapshot.md)）。

## 関連ドキュメント

- [docs/front/mypage-order.md](mypage-order.md) — マイページ共通レイアウトの正本
- [docs/front/auth.md](auth.md) — 会員登録・ログインの入力規則の正本
- [docs/order-snapshot.md](../order-snapshot.md) — 確定済み注文が会員情報を値で保持する規則の正本
- [docs/admin/member.md](../admin/member.md) — 管理画面の会員マスタ（会員情報の編集は会員自身が行う）
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/MyPage/ProfileController.php` |
| Controller | `app/Http/Controllers/Front/MyPage/PasswordController.php` |
| FormRequest | `app/Http/Requests/Front/MyPage/UpdateProfileRequest.php` |
| FormRequest | `app/Http/Requests/Front/MyPage/UpdatePasswordRequest.php` |
| Page | `resources/js/front/Pages/MyPage/Profile.tsx` |
| Page | `resources/js/front/Pages/MyPage/Password.tsx` |
| Test | `tests/Feature/Front/MyPage/ProfileUpdateTest.php` |
| Test | `tests/Feature/Front/MyPage/PasswordUpdateTest.php` |

## 受け入れ条件

- 未ログインでログイン画面へリダイレクトされる: `tests/Feature/Front/MyPage/ProfileUpdateTest.php`・`PasswordUpdateTest.php`（各`未ログインではログイン画面へリダイレクトされる`）
- 現在の会員情報が初期値として表示される: `tests/Feature/Front/MyPage/ProfileUpdateTest.php`（`現在の会員情報が初期値として表示される`）
- 会員情報を更新できる: 同上（`会員情報を更新できる`）
- 自分の現在のメールアドレスのままでも更新できる: 同上（`現在のメールアドレスのまま更新できる`）
- 他の会員が使用中のメールアドレスを弾く: 同上（`他の会員が使用中のメールアドレスは登録できない`）
- 必須・形式の不備を弾き、任意項目は省略できる: 同上（`氏名が未入力なら更新されない`・`カナが全角カタカナ以外なら更新されない`・`カナと電話番号は省略できる`）
- 会員情報を変更しても確定済み注文の注文者情報が変わらない: 同上（`会員情報を変更しても確定済みの注文の注文者名は変わらない`）
- パスワードを変更できる: `tests/Feature/Front/MyPage/PasswordUpdateTest.php`（`パスワードを変更できる`）
- 現在のパスワードの誤り・確認用の不一致・8文字未満・現在と同一を弾く: 同上（`現在のパスワードが誤っていれば変更されない`・`確認用パスワードが一致しなければ変更されない`・`八文字未満のパスワードには変更できない`・`現在と同じパスワードには変更できない`）
- 変更後に新しいパスワードでログインでき、ログイン状態も維持される: 同上（`変更後は新しいパスワードでログインできる`・`変更後もログイン状態が維持される`）
- フォームの表示・エラーの表示位置: 自動テストなし。目視確認で担保する
