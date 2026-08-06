# 単位17: フロント - マイページ

依存: 単位16

左のタブナビゲーションで5機能を切り替える構成。

## スコープ

- 注文履歴: 一覧（注文番号・注文日・ステータス・合計・商品名）・注文詳細・キャンセル依頼
- お気に入り: 一覧（商品カード）・解除
- 配送先住所: 一覧・追加・編集・削除
- 会員情報変更: 氏名・氏名カナ・メールアドレス・電話番号
- パスワード変更: 現在のパスワード・新しいパスワード・確認

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest / Policy） | `laravel-set:laravel` |
| クエリビルダ・eager load | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php`）

すべて `auth` ミドルウェアを適用する。

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/mypage` | `mypage.index` | 注文履歴（既定タブ） |
| GET | `/mypage/orders/{order}` | `mypage.orders.show` | 注文詳細 |
| POST | `/mypage/orders/{order}/cancel` | `mypage.orders.cancel` | キャンセル依頼 |
| GET | `/mypage/favorites` | `mypage.favorites` | お気に入り |
| GET | `/mypage/addresses` | `mypage.addresses` | 配送先住所 |
| PUT | `/addresses/{address}` | `addresses.update` | 配送先更新 |
| DELETE | `/addresses/{address}` | `addresses.destroy` | 配送先削除 |
| GET | `/mypage/profile` | `mypage.profile` | 会員情報変更 |
| PUT | `/mypage/profile` | `mypage.profile.update` | 会員情報更新 |
| GET | `/mypage/password` | `mypage.password` | パスワード変更 |
| PUT | `/mypage/password` | `mypage.password.update` | パスワード更新 |

`POST /addresses`（配送先追加）と `DELETE /favorites/{product}`（お気に入り解除）は単位16・13で実装済み。

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Front/MyPage/OrderHistoryController.php` | `index()` / `show()` |
| Controller | `app/Http/Controllers/Front/MyPage/OrderCancelController.php` | `store()` |
| Controller | `app/Http/Controllers/Front/MyPage/FavoriteListController.php` | `index()` |
| Controller | `app/Http/Controllers/Front/MyPage/AddressListController.php` | `index()` |
| Controller | `app/Http/Controllers/Front/MyPage/ProfileController.php` | `edit()` / `update()` |
| Controller | `app/Http/Controllers/Front/MyPage/PasswordController.php` | `edit()` / `update()` |
| Controller | `app/Http/Controllers/Front/UserAddressController.php` | **修正**: `update()` / `destroy()` を追加 |
| FormRequest | `app/Http/Requests/Front/MyPage/UpdateProfileRequest.php` | 会員情報のバリデーション |
| FormRequest | `app/Http/Requests/Front/MyPage/UpdatePasswordRequest.php` | 現在のパスワード照合＋新パスワードのバリデーション |
| FormRequest | `app/Http/Requests/Front/Address/UpdateUserAddressRequest.php` | 配送先更新のバリデーション |
| Service | `app/Services/Front/Order/CancelOrderService.php` | キャンセル可否の判定・ステータス更新・履歴記録・在庫戻し |
| Policy | `app/Policies/UserAddressPolicy.php` | 自分の配送先のみ操作できることを保証 |
| Policy | `app/Policies/OrderPolicy.php` | **修正**: `view` / `cancel` を追加 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Layout | `resources/js/front/Layouts/MyPageLayout.tsx` | 見出し「マイページ」＋会員名・会員ID＋左タブナビ＋右コンテンツ |
| Component | `resources/js/front/Components/MyPage/TabNav.tsx` | 5タブのナビゲーション（現在タブをブランド色で塗る） |
| Page | `resources/js/front/Pages/MyPage/Orders.tsx` | 注文カードのリスト（注文番号・注文日・ステータス・合計・商品名・詳細／キャンセル依頼ボタン） |
| Page | `resources/js/front/Pages/MyPage/OrderShow.tsx` | 注文詳細（配送先・支払い方法・商品明細・金額内訳・配達予定日・銀行振込案内文・ステータス履歴） |
| Page | `resources/js/front/Pages/MyPage/Favorites.tsx` | 商品カードグリッド＋解除ボタン。0件時は専用の表示 |
| Page | `resources/js/front/Pages/MyPage/Addresses.tsx` | 住所カードのリスト＋編集／削除＋「＋ 配送先を追加」 |
| Page | `resources/js/front/Pages/MyPage/Profile.tsx` | 氏名・氏名カナ・メールアドレス・電話番号のフォーム |
| Page | `resources/js/front/Pages/MyPage/Password.tsx` | 現在のパスワード・新しいパスワード・確認のフォーム |
| Component | `resources/js/front/Components/MyPage/OrderCard.tsx` | 注文1件の要約カード |
| Component | `resources/js/front/Components/Checkout/AddressModal.tsx` | **修正**: 編集モードに対応（既存の住所を初期値として受け取る） |

## インターフェース ＆ データロジック

### Props の型

```ts
// MyPage/Orders.tsx
type OrderSummary = {
  id: number;
  order_number: string;
  ordered_at: string;             // 'YYYY.MM.DD'
  status: OrderStatus;
  status_label: string;
  total: number;
  items_summary: string;          // 'チームジャージ 2026 ほか1点'
  is_cancelable: boolean;
};
type Props = { orders: Paginated<OrderSummary> };

// MyPage/OrderShow.tsx
type Props = {
  order: {
    order_number: string;
    ordered_at: string;
    status: OrderStatus;
    status_label: string;
    payment_method_label: string;
    bank_transfer_note: string | null;
    estimated_delivery_date: string;
    items: { product_name: string; variant_label: string; product_image_url: string | null; unit_price: number; quantity: number; subtotal: number }[];
    subtotal: number;
    shipping_fee: number;
    cod_fee: number;
    total: number;
    shipping: { recipient_name: string; postal_code: string; prefecture_name: string; city: string; address_line1: string; address_line2: string | null; tel: string };
    histories: { to_status_label: string; changed_at: string }[];
    is_cancelable: boolean;
  };
};
```

注文履歴・注文詳細はすべて `orders` / `order_items` のスナップショット列から描画する（`products` を参照しない）。

### バリデーション

`UpdateProfileRequest`:

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大100 |
| `name_kana` | 任意・文字列・最大100・全角カナのみ |
| `email` | 必須・メール形式・最大191・`users.email` で一意（自身を除外） |
| `tel` | 任意・`[\d-]{10,20}` 形式 |

`UpdatePasswordRequest`:

| 項目 | ルール |
|---|---|
| `current_password` | 必須・`current_password` ルール（現在のパスワードと一致） |
| `password` | 必須・確認一致・8文字以上・`Password::defaults()`・**現在のパスワードと異なること** |

`UpdateUserAddressRequest`: `StoreUserAddressRequest`（単位16）と同じルール。

### 注文のキャンセル可否

| 現在のステータス | キャンセル依頼 |
|---|---|
| 注文受付 `received` | 可 |
| 入金待ち `awaiting_payment` | 可 |
| 入金確認済み `payment_confirmed` | 不可（管理者へ連絡が必要） |
| 出荷準備中 `preparing` | 不可 |
| 出荷済み `shipped` | 不可 |
| キャンセル `cancelled` | 不可（既にキャンセル済み） |

### 主要な処理フロー

**注文履歴**: `orders` のうち `user_id` が自分のものを `ordered_at` 降順でページネーション（1ページ10件）。`items_summary` は先頭明細の商品名＋「ほかN点」（明細が1件なら商品名のみ）。

**注文詳細**: `OrderPolicy` で自分の注文であることを確認する。他人の注文IDを指定した場合は 403 を返す。

**キャンセル依頼**（`CancelOrderService`。1トランザクション）:
1. `OrderPolicy` で自分の注文であることを確認する
2. キャンセル可否を再判定する。不可なら「この注文はキャンセルできません」を返す
3. 確認モーダルで確認する
4. `orders.status` を `cancelled` に更新し `cancelled_at` を設定する
5. `order_status_histories` に1行追加する（`admin_id = null`）
6. `RestoreStockFromOrder`（単位07）で在庫を戻す
7. 注文詳細へリダイレクトし「注文をキャンセルしました」をフラッシュ

**配送先の更新・削除**:
- `UserAddressPolicy` で自分の住所であることを確認する
- `is_default` を立てた場合、同じ会員の他の住所の `is_default` を解除する
- 削除は確認モーダルを表示する。**確定済みの注文は住所をスナップショットで保持しているため、住所を削除しても過去注文の表示は変わらない**
- 住所が1件のみのときも削除できる（購入時に新規追加できるため）

**会員情報の更新**: メールアドレスを変更した場合も再認証は求めない（メール認証を使わないため）。

**パスワードの更新**: 現在のパスワードを照合し、新しいパスワードをハッシュして保存する。更新後もセッションは維持する（`Auth::logoutOtherDevices()` は使わない）。

## 業務ルール

- 注文履歴・注文詳細はスナップショット列のみを参照する。商品価格・商品名・カテゴリ名・会員情報・配送先住所・送料設定が後から変わっても表示は変わらない
- 配送先住所を削除しても、その住所で確定した過去注文の配送先表示は変わらない
- キャンセル依頼は「注文受付」「入金待ち」の注文にのみ行える。それ以降は管理画面からの操作が必要
- キャンセル時は在庫を戻す（管理画面からのキャンセルと同じ処理を共用する）
- お気に入りに登録した商品が非公開・削除された場合は一覧に表示しない

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Front/MyPage/OrderHistoryTest.php` | 未ログインでログイン画面へリダイレクトされること／自分の注文のみ表示されること／注文日の降順／`items_summary` の組み立て（1点／複数点）／キャンセル可否フラグ |
| `tests/Feature/Front/MyPage/OrderShowTest.php` | 自分の注文詳細が表示されること／他人の注文が403になること／スナップショット列で描画されること／商品を削除しても明細が表示されること／銀行振込の注文に振込案内文が表示されること |
| `tests/Feature/Front/MyPage/OrderCancelTest.php` | 「注文受付」「入金待ち」の注文をキャンセルできること／「入金確認済み」以降がキャンセルできないこと／キャンセル済みを再キャンセルできないこと／他人の注文をキャンセルできないこと（403）／在庫が戻ること／履歴の `admin_id` が `null` で記録されること |
| `tests/Feature/Front/MyPage/FavoriteListTest.php` | 自分のお気に入りのみ表示されること／非公開商品が除外されること／解除できること／0件時の表示 |
| `tests/Feature/Front/MyPage/AddressTest.php` | 一覧／追加／更新／削除ができること／他人の住所を操作できないこと（403）／`is_default` を立てると他の既定が解除されること／住所を削除しても確定済み注文の配送先表示が変わらないこと |
| `tests/Feature/Front/MyPage/ProfileUpdateTest.php` | 会員情報が更新されること／他の会員が使用中のメールアドレスを弾くこと／自身の現在のメールアドレスは許可されること／会員情報を変更しても確定済み注文の顧客名が変わらないこと |
| `tests/Feature/Front/MyPage/PasswordUpdateTest.php` | パスワードが更新されること／現在のパスワードが誤っていると弾かれること／新旧が同一のとき弾かれること／8文字未満を弾くこと／更新後に新しいパスワードでログインできること／更新後もセッションが維持されること |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **キャンセル可能なステータス**: 要件はマイページに「注文キャンセル導線」とだけ記載。推奨=**「注文受付」「入金待ち」のみ**（入金確認済み以降は返金処理が絡むため管理者操作にする）。
2. **キャンセル依頼か即時キャンセルか**: 要件は「キャンセル依頼」と表現している。推奨=**即時キャンセル**（ステータスを `cancelled` に変更し在庫を戻す）。管理者の承認を挟む運用にする場合は「キャンセル依頼中」ステータスの追加が必要になるため、実行時に指示すること。
3. **タブの切り替え方式**: モックはクライアント側の state で切り替えている。推奨=**URLを分ける**（`/mypage`、`/mypage/favorites` 等）。ブラウザの戻る操作とブックマークが機能し、各タブのデータを必要なときだけ取得できる。
4. **配送先住所の削除制限**: 推奨=**制限しない**（住所はスナップショットで保持されるため過去注文が壊れない）。
5. **メールアドレス変更時の再認証**: 実装しない（メール認証を使わないため）。
6. **パスワード変更後の他端末のログアウト**: 実装しない（セッションを維持する）。
7. **注文履歴のページネーション**: 推奨=1ページ10件。
8. **注文詳細のステータス履歴表示**: 要件に記載はないが、会員が入金確認・出荷の進行を追えるため推奨=**表示する**（管理者名は表示しない）。
9. **会員の退会**: 実装しない（要件に記載なし）。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/front/mypage-order.md`（注文履歴・注文詳細・キャンセル）
  - `docs/front/mypage-address.md`（配送先住所）
  - `docs/front/mypage-profile.md`（会員情報変更・パスワード変更）
  - `docs/front/favorite.md` に**追記**: お気に入り一覧の記述を加える
- `docs/order-snapshot.md` の `## 関連ドキュメント` に本単位の仕様書へのリンクを追加する
- 本計画ファイルを削除し、トラッカーの状態を更新する
