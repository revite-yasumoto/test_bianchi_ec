# フロント お気に入り

## 機能概要

- **対象画面・機能の目的:** 会員が商品をお気に入りに登録・解除する。
- **URL / メソッド:**
  - `POST /favorites`（ルート名 `favorites.store`）
  - `DELETE /favorites/{product}`（ルート名 `favorites.destroy`）
- **アクセス権限・ミドルウェア:** `auth`。未ログインで押した場合はログイン画面へ遷移し、ログイン後は操作元の商品詳細へ戻る。
- **本ドキュメントのスコープ:** 登録・解除の操作と、商品詳細に置くトグルボタン。お気に入り一覧の画面はマイページ（単位17）が扱う。

## 使用テーブル

| テーブル | 用途 |
|---|---|
| `favorites` | 会員と商品の対（`user_id` + `product_id` で一意） |
| `products` | 登録対象。`is_published = false` は登録できない |

定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
未登録:  [ お気に入りに追加 ]   … 枠線 line・文字 ink
登録済み: [ お気に入り済     ]   … 枠線 coral・文字 coral
```

状態はラベルと配色、および `aria-pressed` で示す（モックはハート記号を使うが、`♡`・`♥` はJIS X 0208外のためテキストに置き換える）。

- 商品詳細の「カートに入れる」の右に並べる（購入導線と区別するためアウトライン表示）。
- 押下後は `preserveScroll` でその場に留まり、状態が切り替わる。ヘッダーのお気に入り件数も更新される。

## インターフェース ＆ データロジック

### データ構造・型定義

```ts
// resources/js/front/Components/Product/FavoriteButton.tsx
type FavoriteButtonProps = {
    productId: number;
    isFavorited: boolean;
};
```

送信データは登録が `{ product_id: number }`、解除はURLパラメータの商品IDのみ。

### 入力値バリデーションルール（`POST /favorites`）

| 項目 | ルール |
|---|---|
| `product_id` | 必須・整数・`products.id` に存在し、かつ `is_published = true` |

### 主要な処理フロー

**登録**

1. 未ログインなら `auth` ミドルウェアがログイン画面へ遷移させる。
2. `(user_id, product_id)` で作成する。既にあれば作成しない（二重登録で行が増えない）。
3. 「お気に入りに追加しました」をフラッシュする。

**解除**

1. ログイン中の会員のお気に入りのみを対象に削除する（他会員の行は削除できない）。
2. 「お気に入りを解除しました」をフラッシュする。

## 業務ルール

- お気に入りは会員に紐づくため、未ログインでは保持しない（ゲスト用の一時保存は行わない）。

## 関連ドキュメント

- [docs/front/product-show.md](product-show.md) — お気に入りボタンを置く商品詳細
- [docs/front/common-layout.md](common-layout.md) — ヘッダーのお気に入り件数（`favoriteCount`）の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/FavoriteController.php` |
| FormRequest | `app/Http/Requests/Front/Favorite/StoreFavoriteRequest.php` |
| Component | `resources/js/front/Components/Product/FavoriteButton.tsx` |
| Test | `tests/Feature/Front/Favorite/FavoriteTest.php` |

## 受け入れ条件

- お気に入りに登録できる: `tests/Feature/Front/Favorite/FavoriteTest.php`（`お気に入りに登録できる`）
- お気に入りを解除できる: 同上（`お気に入りを解除できる`）
- 同じ商品を二重に登録しても行が増えない: 同上（`同じ商品を二重に登録しても行は増えない`）
- 他会員のお気に入りを解除できない: 同上（`他の会員のお気に入りは解除できない`）
- 非公開商品を登録できない: 同上（`非公開商品はお気に入りに登録できない`）
- 未ログインでログイン画面へリダイレクトされる: 同上（`未ログインではログイン画面へリダイレクトされる`）
- ボタンの表示切り替え（♡／♥・配色）: 自動テストなし。目視確認で担保する
