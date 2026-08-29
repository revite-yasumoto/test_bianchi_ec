# 会員登録・ログイン・ログアウト

## 機能概要

- **対象画面・機能の目的:** `web` ガードによる会員の会員登録・ログイン・ログアウト。管理者（管理画面）とは完全に独立した認証（[docs/1_system_overview.md](../1_system_overview.md) 参照）。ゲスト購入は不可のため、購入導線は本機能の認証を前提とする。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 認証 |
|---|---|---|---|
| GET | `/` | `top` | なし |
| GET | `/register` | `register` | `guest` |
| POST | `/register` | `register.store` | `guest` |
| GET | `/register/complete` | `register.complete` | `guest` |
| GET | `/login` | `login` | `guest` |
| POST | `/login` | `login.store` | `guest` |
| POST | `/logout` | `logout` | `auth` |

- **アクセス権限・ミドルウェア:** 上表の通り。フロント側のルート名は `admin.` のような prefix を付けない。`POST /register` には `throttle:5,60`（同一の送信元から1時間に5回まで）を適用する。1リクエストで会員の作成とメール送信が起きるため。パスワード再設定のルートは [docs/front/password-reset.md](password-reset.md) が正本。
- **本ドキュメントのスコープ:** 会員登録・ログイン・ログアウトのバックエンド処理、会員登録画面（`Register.tsx`）・会員登録完了画面（`RegisterComplete.tsx`）・ログイン画面（`Login.tsx`）。遷移先のTOPページは [docs/front/top.md](top.md) が正本。各画面が使う共通レイアウト・共通UIコンポーネント・デザイントークンは [docs/front/common-layout.md](common-layout.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `users` | 会員を作成する。ログイン失敗の連続回数・ロックの期限も持つ |
| `user_addresses` | 会員登録の住所を既定の配送先として1件作成する |
| `prefectures` | 住所の都道府県の選択肢（読み取りのみ） |

定義は [docs/2_database.md](../2_database.md) が正本。次の列を本機能で追加する。

| カラム | 型 | 用途 |
|---|---|---|
| `users.failed_login_attempts` | `unsignedTinyInteger`・NOT NULL・既定 `0` | ロックに至るまでの連続失敗回数。成功・ロック成立のいずれでも `0` に戻す |
| `users.locked_until` | `datetime`・nullable | ロックの解除時刻。`null` はロックされていない状態 |
| `users.birthday` | `date`・nullable | 生年月日（任意項目） |
| `users.gender` | `string(10)`・nullable | `App\Enums\Gender`。未回答は `null` |
| `users.is_newsletter_subscribed` | `boolean`・NOT NULL・既定 `true` | メールマガジンの購読可否 |

`email_verified_at`（メール認証）は Laravel 標準として存在するが、本システムでは使用しない。`password_reset_tokens` は [docs/front/password-reset.md](password-reset.md) が使用する。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
ログイン（GET /login） 最大幅420px・中央寄せ:
+--------------------------------------+
|            ログイン                   |
|  ご購入には会員登録・ログインが必要です  |
|  メールアドレス                        |
|  [                                 ]  |
|  パスワード                            |
|  [                                 ]  |
|  [ ] この端末でログイン状態を保持する    |
|  [        ログイン        ]           |
|      新規会員登録はこちら →            |
+--------------------------------------+

会員登録（GET /register） 最大幅520px・中央寄せ:
+--------------------------------------+
|            会員登録                   |
|  お名前*          |  お名前（カナ）    |   ← SP幅では1カラム、sm以上で2カラム
|  メールアドレス*（全幅）                |
|  パスワード*（全幅）                    |
|  パスワード（確認）*（全幅）             |
|  電話番号*        |  性別              |
|  生年月日（全幅）  [年v] [月v] [日v]    |
|                                      |
|  住所                    （小見出し）    |
|  郵便番号*（全幅）  [123] − [4567]      |
|  都道府県*        |  市区町村*          |
|  番地*（全幅）                          |
|  建物名・部屋番号（全幅）                 |
|                                      |
|  [v] メールマガジンを受け取る            |
|  [ ] 利用規約 および プライバシーポリシー |
|      に同意します                      |
|  [        登録する        ]           |
|  すでにアカウントをお持ちの方は ログイン  |
+--------------------------------------+

会員登録完了（GET /register/complete） 最大幅520px・中央寄せ:
+--------------------------------------+
|         (チェックアイコン)             |
|       会員登録が完了しました            |
|  ご登録のメールアドレスへ完了メールを     |
|  お送りしました。ログインしてお買い物を   |
|  お楽しみください。                     |
|  [ ログインする ] [ TOPへ戻る ]        |
+--------------------------------------+
```

- エラー表示: 各入力欄の直下にインラインで表示する。認証失敗・レート制限・休会中のメッセージは `email` の直下にまとめて表示する。
- 必須項目のラベルには `*` を表示する。
- 同意チェックの「利用規約」「プライバシーポリシー」はそれぞれの静的ページ（[docs/front/static-pages.md](static-pages.md)）へのリンクにする。リンクはチェックボックスのラベル内に置く（ラベル内の対話的要素へのクリックではチェックが切り替わらない）。
- メールマガジンのチェックボックスは既定でチェック済みにする。同意チェックとは別の欄として独立させ、ラジオボタン（受け取る／受け取らない）にはしない。
- 住所の入力欄は「住所」の小見出しで区切り、「ご注文時のお届け先として登録します。」と用途を添える。郵便番号欄・自動セットの挙動は [docs/front/postal-code-lookup.md](postal-code-lookup.md) が正本で、配送先モーダルと同じ `PostalCodeField` を使う。
- 生年月日は年・月・日の3つのプルダウンで選ぶ。年は当年から100年前までを新しい順に並べ、いずれも未選択（`--`）を先頭に置く。
- 性別のプルダウンは未選択（`--`）・男性・女性の3択にする。
- 表示名（`user_addresses.label`）・宛名（`recipient_name`）の入力欄は置かない（下記の処理フロー参照）。

## インターフェース ＆ データロジック

- **データ構造・型定義:**

```ts
type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

type RegisterForm = {
    name: string;
    name_kana: string;
    email: string;
    password: string;
    password_confirmation: string;
    tel: string;
    // 生年月日は3つのプルダウンで選び、未選択は空文字。サーバー側で日付に組み立てる
    birth_year: string;
    birth_month: string;
    birth_day: string;
    gender: string;              // '' | 'male' | 'female'
    postal_code: string;         // 保存する形（`123-4567`）
    prefecture_id: number;
    city: string;
    address_line1: string;
    address_line2: string;
    is_newsletter_subscribed: boolean;
    agree: boolean;
};
```

会員登録画面のProps:

```ts
// resources/js/front/Pages/Auth/Register.tsx
type Props = {
    prefectures: { id: number; name: string }[];
};
```

共有プロパティ（`FrontSharedProps`）の型と共有範囲は [docs/front/common-layout.md](common-layout.md) が正本。

- **入力値バリデーションルール（`Front\Auth\RegisterRequest`）:**

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `name_kana` | 任意・文字列・最大100・全角カタカナ／長音符／空白のみ（`/\A[ァ-ヶー\x{3000}\s]+\z/u`） |
| `email` | 必須・文字列・メール形式・最大191・`users.email` で一意 |
| `password` | 必須・`password_confirmation` と一致・8文字以上 |
| `tel` | 必須・文字列・数字とハイフンで10〜20文字（`/\A[\d-]{10,20}\z/`） |
| `birth_year` / `birth_month` / `birth_day` | 任意・整数。3つすべて選ぶか、すべて未選択のどちらか。揃っている場合は実在する日付で、当日以前であること |
| `gender` | 任意・`male` / `female` のいずれか |
| `postal_code` | 必須・文字列・最大8・`/\A\d{3}-?\d{4}\z/` |
| `prefecture_id` | 必須・整数・`prefectures.id` に存在 |
| `city` | 必須・文字列・最大100 |
| `address_line1` | 必須・文字列・最大255 |
| `address_line2` | 任意・文字列・最大255 |
| `is_newsletter_subscribed` | 任意・boolean（未送信は `false` として扱う） |
| `agree` | `accepted`（利用規約・プライバシーポリシーへの同意） |

住所・電話番号のルールは配送先の追加（[docs/front/mypage-address.md](mypage-address.md)・[docs/front/checkout.md](checkout.md)）と同一にする。生年月日は3項目の組み合わせで検証するため、エラーは `birth_year` にまとめて返す。

- **入力値バリデーションルール（`Front\Auth\LoginRequest`）:**

| 項目 | ルール |
|---|---|
| `email` | 必須・文字列・メール形式 |
| `password` | 必須・文字列 |
| `remember` | 任意・boolean |

- レート制限: `email`（小文字化）＋ IPアドレスをキーに5回／分。超過時は残り秒数を含むメッセージを `email` に返す。

- **主要な処理フロー:**

**会員登録（`RegisteredUserController::store()`）:**
1. `RegisterRequest` でバリデーションする。
2. 生年月日の3項目が揃っていれば日付に組み立て、未選択なら `null` にする。
3. `RegisterUser` が `GenerateMemberCode` で会員IDを採番し（`users.member_code` の最大値の数値部分 + 1、`M-` + 6桁ゼロ埋め。会員が1件も無い場合は `M-100001`）、`users` に `status = active` で作成する。
4. 同じトランザクション内で `user_addresses` に既定の配送先（`is_default = true`）を1件作成する。`label` は `自宅`、`recipient_name` は入力された氏名（`name`）、`tel` は入力された電話番号を入れる。会員の作成と住所の作成は一方だけが残らないようにする。
5. 続けて登録完了メールを会員へ送る（トランザクションの外）。送信の仕様は [docs/mail-notification.md](../mail-notification.md) が正本。
4. ログインはせず、セッションに完了画面の表示許可を入れて `register.complete` へリダイレクトする。
5. 未ログインで要認証ページから来た場合の遷移先（intended URL）はここで消費せず、後続のログインまで持ち越す。

**会員登録完了（`RegisteredUserController::complete()`）:** セッションの表示許可を取り出して消し、完了画面を返す。許可が無ければ `login` へリダイレクトする（登録していない訪問者に完了画面を見せないため）。

**ログイン（`AuthenticatedSessionController::store()`）:**
1. レート制限を確認する（超過時は `email` にエラーを返す）。
2. 入力されたアドレスの会員がロック中（`locked_until` が現在時刻より後）なら、認証を試みずに「アカウントを一時的にロックしています。約N分後に再度お試しください。」を `email` に返す。
3. `Auth::guard('web')->attempt()` で認証する。
4. 失敗時はレート制限のカウントを加算し、あわせて `failed_login_attempts` を1つ増やす。3回に達したら `locked_until` に1時間後を設定して回数を `0` に戻す。この読み取りと書き込みは対象行の排他ロックで挟む（挟まないと、同時に届いた試行がどれも同じ回数を読み、上限に達するまでのラウンド数だけ試行できてしまう）。返すメッセージは、ロックが成立した場合は 2. と同じ文面、それ以外は「メールアドレスまたはパスワードが誤っています。」（アカウントの存在有無を区別しない）。
5. 認証に成功しても `status` が `active` でない会員はログアウトさせ、`email` にエラーを返す。`suspended` は「アカウントが利用停止中です。お問い合わせください。」、`withdrawn` は「このアカウントは退会済みです。」。このとき資格情報自体は正しいため、`failed_login_attempts` と `locked_until` は戻す。
6. 成功時はレート制限をクリアし、`failed_login_attempts` を `0`・`locked_until` を `null` に戻す。セッションを再生成（`session()->regenerate()`）した上で intended URL（無ければ `top`）へリダイレクトする。

ロックの解除は `locked_until` の経過のみで行う。管理画面からの手動解除は設けない。

**ログアウト（`AuthenticatedSessionController::destroy()`）:** `web` ガードからログアウト → セッション無効化（`invalidate()`） → CSRFトークン再生成（`regenerateToken()`） → `top` へリダイレクトし、`success` フラッシュに「ログアウトしました。」を渡す。

## 業務ルール

- ログイン失敗時のメッセージは、メールアドレスの登録有無を区別できる内容にしない（アカウント列挙攻撃対策）。休会中・退会済みのメッセージのみ、利用者が次の行動を判断できるよう区別して返す。いずれも正しいパスワードを知っていなければ到達しないため、列挙の手段にはならない。
- 会員登録で作る配送先は既定（`is_default = true`）にし、マイページから削除できない（[docs/front/mypage-address.md](mypage-address.md) が削除の正本）。購入手続きに進んだ時点で配送先が1件も無い状態を作らないため。
- メールマガジンの購読は既定で `true`（チェック済み）にする。ラジオボタン（受け取る／受け取らない）にはしない。
- 生年月日・性別は任意項目とし、未入力・未選択のまま登録できる。性別のEnumに「未回答」の値は持たせず、列を `null` にして表す。
- 登録後の電話番号・生年月日・性別・メールマガジンの変更は会員情報変更（[docs/front/mypage-profile.md](mypage-profile.md)）で行う。
- 会員登録の完了時に自動ログインはしない。完了画面からログイン画面へ案内し、会員自身にログインしてもらう。購入手続きの途中で登録した場合も同じで、ログイン後に元の手続きへ戻る。
- ログイン失敗の抑止は2層で行う。レート制限（メールアドレス＋IP）は同一の送信元からの連投を、アカウントロック（アカウント単位）は送信元を変えた総当たりを抑える。ロックの回数はレート制限より少ないため、同じアカウントを狙う限りロックが先に成立する。
- ロック中であることは利用者へ伝える。ロックは3回の試行を要するため列挙の手段としては割に合わず、伝えないと本人が理由を判断できないまま問い合わせに至る。休会中のメッセージと同じ扱いとする。
- メール認証（`email_verified_at`）は実装しない（要件の画面一覧に含まれないため）。パスワードリセットは [docs/front/password-reset.md](password-reset.md) が正本。
- 会員IDの採番は登録時点の最大値 + 1 で行うため、同時登録が競合した場合は `users.member_code` の一意制約で弾かれる。
- 未ログインで要認証ルートへアクセスした場合、Laravel 標準の `Authenticate` ミドルウェアが intended URL をセッションに保存し、ログイン成功後に元の手続きへ戻す。フロント向けの遷移先は `bootstrap/app.php` の `redirectGuestsTo`（`login`）／`redirectUsersTo`（`top`）で決まる。管理画面向けの分岐は [docs/admin/auth.md](../admin/auth.md) が正本。

## 関連ドキュメント

- [docs/front/common-layout.md](common-layout.md) — フロント共通レイアウト・共通UIコンポーネント・デザイントークンの正本。`FrontSharedProps` の型定義もこちらが正本
- [docs/front/top.md](top.md) — ログイン後・ログアウト後の遷移先であるTOPページの正本
- [docs/front/static-pages.md](static-pages.md) — 同意チェックからリンクする利用規約・プライバシーポリシーの正本
- [docs/admin/auth.md](../admin/auth.md) — 管理者認証の正本。`bootstrap/app.php` のパス分岐・`config/inertia.php` の設定はこちらが正本
- [docs/front/password-reset.md](password-reset.md) — パスワード再設定の正本。ログイン画面から導線を持つ
- [docs/front/withdrawal.md](withdrawal.md) — 退会の正本。`withdrawn` を設定する唯一の経路
- [docs/mail-notification.md](../mail-notification.md) — 登録完了メールの正本
- [docs/1_system_overview.md](../1_system_overview.md) — 2ガード構成の前提
- [docs/2_database.md](../2_database.md) — `users` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Controller | `app/Http/Controllers/Front/Auth/AuthenticatedSessionController.php` |
| Controller | `app/Http/Controllers/Front/Auth/RegisteredUserController.php` |
| FormRequest | `app/Http/Requests/Front/Auth/LoginRequest.php` |
| FormRequest | `app/Http/Requests/Front/Auth/RegisterRequest.php` |
| Migration | `database/migrations/2026_08_14_000001_add_login_lock_columns_to_users_table.php` |
| Migration | `database/migrations/2026_08_24_000001_add_profile_columns_to_users_table.php` |
| Enum | `app/Enums/Gender.php` |
| Trait | `app/Http/Requests/Concerns/ValidatesBirthdayParts.php` |
| Action | `app/Actions/Front/Auth/GenerateMemberCode.php` |
| Action | `app/Actions/Front/Auth/RegisterUser.php` |
| ルート | `routes/web.php` |
| Page | `resources/js/front/Pages/Auth/Login.tsx` |
| Page | `resources/js/front/Pages/Auth/Register.tsx` |
| Component | `resources/js/front/Components/Auth/BirthdayField.tsx` |
| Component | `resources/js/front/Components/Checkout/PostalCodeField.tsx` |
| Page | `resources/js/front/Pages/Auth/RegisterComplete.tsx` |
| 区分値 | `resources/js/shared/lib/enums.ts` |
| Test | `tests/Feature/Front/Auth/LoginTest.php` |
| Test | `tests/Feature/Front/Auth/LogoutTest.php` |
| Test | `tests/Feature/Front/Auth/RegisterTest.php` |

## 受け入れ条件

- 会員登録画面が正しいInertiaコンポーネントで表示される: `tests/Feature/Front/Auth/RegisterTest.php`（`会員登録画面が表示される`）
- 会員登録するとユーザーが作成され、ログインしないまま完了画面へ遷移する: 同上（`会員登録するとユーザーが作成され完了画面へ遷移する`・`会員登録してもログイン状態にはならない`）
- 会員登録画面に都道府県の選択肢が渡る: 同上（`会員登録画面に都道府県の選択肢が渡る`）
- 電話番号・住所が未入力なら登録されない: 同上（`電話番号が未入力なら登録されない`・`住所が未入力なら登録されない`）
- 登録すると既定の配送先が1件作られ、表示名・宛名・電話番号が入る: 同上（`会員登録すると既定の配送先が作られる`）
- 生年月日・性別・メールマガジンの購読が保存される: 同上（`生年月日と性別が保存される`・`メールマガジンを受け取る状態で登録できる`・`メールマガジンのチェックを外すと購読しない状態で保存される`）
- 生年月日・性別は未選択でも登録できる: 同上（`生年月日と性別は未選択でも登録できる`）
- 生年月日を一部だけ選ぶと登録されない・実在しない日付や未来の日付を弾く: 同上（`生年月日を一部だけ選ぶと登録されない`・`実在しない生年月日は登録されない`・`未来の生年月日は登録されない`）
- 登録に失敗したとき会員も配送先も作られない: 同上（`住所の検証に失敗すると会員も配送先も作られない`）
- 完了画面は登録直後の一度だけ開ける: 同上（`登録直後は会員登録完了画面を開ける`・`登録を経ずに会員登録完了画面を開くとログイン画面へ戻される`・`会員登録完了画面は再読み込みすると開けない`）
- 購入手続きから登録した場合、ログイン後に購入手続きへ戻る: 同上（`購入手続きから登録するとログイン後に購入手続きへ戻る`）
- 会員が1件も無いとき会員IDが `M-100001` で採番される: 同上（`会員が1件も無いとき会員番号は開始番号で採番される`）
- 会員IDが既存の最大連番 + 1 で採番される: 同上（`会員番号は既存の最大連番の次の番号で採番される`）
- 登録済みメールアドレスでは登録できない: 同上（`登録済みのメールアドレスでは登録できない`）
- パスワードが8文字未満・確認用と不一致では登録できない: 同上（`パスワードが8文字未満では登録できない`・`パスワードと確認用パスワードが一致しないと登録できない`）
- 同意チェックなしでは登録できない: 同上（`利用規約に同意しないと登録できない`）
- 氏名カナは全角カタカナのみ受け付け、未入力は許可される: 同上（`氏名カナが全角カタカナ以外では登録できない`・`氏名カナは未入力でも登録できる`）
- ログイン済みは会員登録画面を開けない: 同上（`ログイン済みの会員は会員登録画面を開けない`）
- 登録の成否に応じて登録完了メールが送られる: 同上（`会員登録すると登録完了メールが送られる`・`登録に失敗したときはメールが送られない`）
- ログイン画面が正しいInertiaコンポーネントで表示される: `tests/Feature/Front/Auth/LoginTest.php`（`ログイン画面が表示される`）
- 正しい認証情報でログインできる: 同上（`正しいメールアドレスとパスワードでログインできる`）
- 誤パスワード・未登録メールではログインできない: 同上（`パスワードが誤っている場合はログインできない`・`未登録のメールアドレスではログインできない`）
- 休会中の会員はログインできない: 同上（`休会中の会員はログインできない`）
- 5回失敗するとレート制限される: 同上（`ログイン失敗が5回続くとレート制限がかかる`）
- 3回失敗するとアカウントがロックされ、正しいパスワードでも入れない: 同上（`パスワードを三回間違えるとアカウントがロックされる`・`ロック中は正しいパスワードでもログインできない`）
- 2回の失敗ではロックされない: 同上（`二回の失敗ではロックされない`）
- 存在しないアドレスへの連続失敗では記録が残らない: 同上（`存在しないアドレスへの連続した失敗ではロックの記録が残らない`）
- 休会中の会員が正しいパスワードで試すと失敗回数が戻る: 同上（`休会中の会員が正しいパスワードで試すと失敗回数が戻る`）
- 失敗回数の加算が同時アクセスで取りこぼされないこと: SQLite（テスト用DB）は行ロック構文を解釈しないため自動テストで担保できない。本番と同じ MySQL の環境での確認が必要
- ロックは1時間の経過で解除される: 同上（`ロックは一時間後に解除される`）
- ログインに成功すると失敗回数が戻る: 同上（`ログインに成功すると失敗回数がリセットされる`）
- 会員登録の連投がレート制限で拒否される: `tests/Feature/Front/Auth/RegisterTest.php`（`同一の送信元からの連続した会員登録は制限される`）
- ログイン成功時にセッションIDが再生成される: 同上（`ログイン成功時にセッション識別子が再生成される`）
- ログイン後に認証前のURLへ復帰する: 同上（`ログイン後は認証前にアクセスしようとしたページへ戻る`）
- 未ログインで要認証ルートへアクセスするとログイン画面へリダイレクトされる: 同上（`未ログインで要認証ルートへアクセスするとログイン画面へ送られる`）
- ログイン済みはログイン画面を開けない: 同上（`ログイン済みの会員はログイン画面を開けない`）
- ログアウトでTOPへ戻り未認証になる: `tests/Feature/Front/Auth/LogoutTest.php`（`ログアウトするとトップページへ戻り未認証になる`）
- ログアウトでセッションが無効化される: 同上（`ログアウト時にセッションが無効化される`）
- 会員登録・ログイン画面のレスポンシブ表示（SP1カラム／sm以上2カラム）: 自動テストなし。目視確認で担保する
