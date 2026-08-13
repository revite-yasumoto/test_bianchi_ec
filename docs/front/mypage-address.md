# フロント マイページ 配送先住所

## 機能概要

- **対象画面・機能の目的:** 会員が自分の配送先住所を一覧・追加・編集・削除する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/mypage/addresses` | `mypage.addresses` | 配送先住所の一覧（マイページのタブ） |
| POST | `/addresses` | `addresses.store` | 追加（[docs/front/checkout.md](checkout.md) が正本） |
| PUT | `/addresses/{address}` | `addresses.update` | 更新 |
| DELETE | `/addresses/{address}` | `addresses.destroy` | 削除 |

- **アクセス権限・ミドルウェア:** `auth`（`web` ガード）。更新は `UserAddressPolicy::update`、削除は `UserAddressPolicy::delete` を `can` ミドルウェアで適用する。いずれも他人の住所は403。
- **本ドキュメントのスコープ:** 配送先住所の一覧・更新・削除と、追加・編集で共用するモーダル。追加処理と入力欄の定義は [docs/front/checkout.md](checkout.md) が正本。マイページ共通レイアウトは [docs/front/mypage-order.md](mypage-order.md) が正本。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `user_addresses` | 会員の配送先。既定は会員あたり1件以下 |
| `prefectures` | 都道府県の選択肢と表示名 |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
┌──────────────────────────────────────┐
│ 架空 太郎（自宅） [既定]                │
│ 〒150-0041 東京都渋谷区… / 090-…       │
│ [ 編集 ] [ 削除 ]                      │
└──────────────────────────────────────┘
┌ ＋ 配送先を追加 ─────────────────────┐  ← 破線の枠
└──────────────────────────────────────┘
```

- 既定の住所を先頭に並べ、以降はID順にする。既定の住所には「既定」バッジを付ける。
- 「編集」「＋ 配送先を追加」は同じモーダルを開く（編集時は既存の値を初期値に入れ、タイトルを「お届け先の編集」、送信ボタンを「保存する」にする）。
- 「削除」は確認モーダルを挟む。モーダルには宛名・表示名と、確定済み注文の表示が変わらない旨を出す。
- 登録が0件のときは「登録済みの配送先はありません。」を出す（追加ボタンは常に出す）。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Pages/MyPage/Addresses.tsx
type Props = {
    addresses: AddressData[];                       // 既定を先頭にした一覧
    prefectures: { id: number; name: string }[];
};

// resources/js/front/Components/Checkout/AddressModal.tsx
type AddressModalProps = {
    isOpen: boolean;
    prefectures: { id: number; name: string }[];
    shippingByPrefecture?: Record<number, ShippingOption>;  // 渡したときだけ送料・お届け日数を案内する
    address?: AddressData;                                  // 渡すと編集モード
    onClose: () => void;
};
```

`AddressData` の定義は [docs/front/checkout.md](checkout.md) が正本。

### 入力値バリデーションルール（`Front\Address\UpdateUserAddressRequest`）

追加（`StoreUserAddressRequest`）と同一のルールから、購入手続き専用の `use_for_checkout` を除いたもの。項目とルールは [docs/front/checkout.md](checkout.md) が正本。

### 主要な処理フロー

**一覧**

1. ログイン中の会員の住所を、既定を先頭・以降ID順で取得する。
2. 都道府県の一覧を選択肢として渡す。

**更新（`UpdateUserAddress`。全体を1トランザクションで囲む）**

1. `UserAddressPolicy::update` で自分の住所であることを確認する（他人の住所は403）。
2. 入力を検証して更新する。
3. 既定に指定した場合は、同じ会員の他の住所の既定を解除する（既定は常に1件以下）。
4. 元の画面へ戻して `お届け先を更新しました` を表示する。

**削除**

1. `UserAddressPolicy::delete` で自分の住所であることを確認する（他人の住所は403）。
2. 住所を削除する。
3. 元の画面へ戻して `お届け先を削除しました` を表示する。

## 業務ルール

- 配送先は残り1件でも削除できる。購入手続きの場でも追加できるため、0件の状態を許容する。
- 確定済みの注文は配送先を値として保持しているため（[docs/order-snapshot.md](../order-snapshot.md)）、住所を削除・変更しても過去の注文の表示は変わらない。

## 関連ドキュメント

- [docs/front/checkout.md](checkout.md) — 配送先の追加処理・入力欄の定義・`AddressData` の正本
- [docs/front/mypage-order.md](mypage-order.md) — マイページ共通レイアウトの正本
- [docs/order-snapshot.md](../order-snapshot.md) — 確定済み注文が配送先を値で保持する規則の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/MyPage/AddressListController.php` |
| Controller | `app/Http/Controllers/Front/UserAddressController.php` |
| Action | `app/Actions/Front/Address/UpdateUserAddress.php` |
| FormRequest | `app/Http/Requests/Front/Address/UpdateUserAddressRequest.php` |
| Policy | `app/Policies/UserAddressPolicy.php` |
| Page | `resources/js/front/Pages/MyPage/Addresses.tsx` |
| Component | `resources/js/front/Components/MyPage/AddressCard.tsx` |
| Component | `resources/js/front/Components/Checkout/AddressModal.tsx` |
| Test | `tests/Feature/Front/MyPage/AddressTest.php` |

## 受け入れ条件

- 未ログインでログイン画面へリダイレクトされる: `tests/Feature/Front/MyPage/AddressTest.php`（`未ログインではログイン画面へリダイレクトされる`）
- 自分の配送先だけが一覧に出て、既定が先頭に並ぶ: 同上（`自分の配送先だけが一覧に表示される`・`既定の配送先が先頭に並ぶ`）
- 配送先を更新できる: 同上（`配送先を更新できる`）
- 更新で既定を付け替えると他の既定が解除される: 同上（`更新で既定に指定すると他の住所の既定が解除される`）
- 入力に不備があれば更新されない: 同上（`入力に不備があれば更新されない`）
- 配送先を削除できる（残り1件でも可）: 同上（`配送先を削除できる`・`配送先が一件だけでも削除できる`）
- 他人の配送先を更新・削除できない: 同上（`他人の配送先は更新できない`・`他人の配送先は削除できない`）
- 削除しても確定済み注文のお届け先が変わらない: 同上（`配送先を削除しても確定済みの注文のお届け先は変わらない`）
- モーダルの編集モード表示・削除確認モーダルの表示: 自動テストなし。目視確認で担保する
