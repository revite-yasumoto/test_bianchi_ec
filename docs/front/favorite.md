# フロント お気に入り

## 機能概要

- **対象画面・機能の目的:** 会員が商品をお気に入りに登録・解除し、登録済みの商品を一覧で確認する。
- **URL / メソッド:**

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| POST | `/favorites` | `favorites.store` | 登録 |
| DELETE | `/favorites/{product}` | `favorites.destroy` | 解除 |
| GET | `/mypage/favorites` | `mypage.favorites` | 一覧（マイページのタブ） |

- **アクセス権限・ミドルウェア:** `auth`。未ログインで押した場合はログイン画面へ遷移し、ログイン後は操作元の商品詳細へ戻る。
- **本ドキュメントのスコープ:** 登録・解除の操作、商品詳細に置くトグルボタン、マイページのお気に入り一覧。マイページ共通レイアウトは [docs/front/mypage-order.md](mypage-order.md) が正本。

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

### お気に入り一覧（マイページ）

```
┌──────────┬──────────┬──────────┐  ← SP2列 / PC3列
│ 商品カード │ 商品カード │ 商品カード │
│[お気に入り│[お気に入り│[お気に入り│
│ から外す] │ から外す] │ から外す] │
└──────────┴──────────┴──────────┘
```

- 商品カードは商品一覧・TOPと同じ `ProductCard` を使い、その下に解除ボタンを置く（カード全体が商品詳細へのリンクのため、ボタンをカードの中に入れない）。
- 解除は `preserveScroll` でその場に留まり、一覧から消える。
- 登録が0件のときは「お気に入りに登録した商品はありません」と商品一覧への導線を出す。

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

```ts
// resources/js/front/Pages/MyPage/Favorites.tsx
type Props = { products: ProductCardData[] };
```

`ProductCardData` の定義は [docs/front/product-index.md](product-index.md) が正本。

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

**一覧**

1. ログイン中の会員のお気に入りのうち、`products.is_published = true` の商品だけを取得する。
2. 並び順はお気に入りの登録が新しい順（`favorites.id` 降順）。
3. 在庫の二値（在庫あり／在庫切れ）を商品カードと同じ集計で付与する。ページ送りは行わない。

## 業務ルール

- お気に入りは会員に紐づくため、未ログインでは保持しない（ゲスト用の一時保存は行わない）。
- 登録後に非公開・削除された商品は一覧に出さない（お気に入りの行は残す）。

## 関連ドキュメント

- [docs/front/product-show.md](product-show.md) — お気に入りボタンを置く商品詳細
- [docs/front/product-index.md](product-index.md) — 商品カード（`ProductCardData`・在庫の二値表示）の正本
- [docs/front/mypage-order.md](mypage-order.md) — マイページ共通レイアウトの正本
- [docs/front/common-layout.md](common-layout.md) — ヘッダーのお気に入り件数（`favoriteCount`）の正本
- [docs/2_database.md](../2_database.md) — テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/Front/FavoriteController.php` |
| Controller | `app/Http/Controllers/Front/MyPage/FavoriteListController.php` |
| FormRequest | `app/Http/Requests/Front/Favorite/StoreFavoriteRequest.php` |
| Component | `resources/js/front/Components/Product/FavoriteButton.tsx` |
| Page | `resources/js/front/Pages/MyPage/Favorites.tsx` |
| Test | `tests/Feature/Front/Favorite/FavoriteTest.php` |
| Test | `tests/Feature/Front/MyPage/FavoriteListTest.php` |

## 受け入れ条件

- お気に入りに登録できる: `tests/Feature/Front/Favorite/FavoriteTest.php`（`お気に入りに登録できる`）
- お気に入りを解除できる: 同上（`お気に入りを解除できる`）
- 同じ商品を二重に登録しても行が増えない: 同上（`同じ商品を二重に登録しても行は増えない`）
- 他会員のお気に入りを解除できない: 同上（`他の会員のお気に入りは解除できない`）
- 非公開商品を登録できない: 同上（`非公開商品はお気に入りに登録できない`）
- 未ログインでログイン画面へリダイレクトされる: 同上（`未ログインではログイン画面へリダイレクトされる`）
- 一覧に自分のお気に入りだけが出る: `tests/Feature/Front/MyPage/FavoriteListTest.php`（`自分のお気に入りだけが表示される`）
- 一覧が登録の新しい順に並ぶ: 同上（`登録が新しい順に並ぶ`）
- 非公開商品が一覧に出ない: 同上（`非公開の商品は表示されない`）
- 一覧で在庫の二値が判別できる: 同上（`在庫のある商品は在庫切れとして表示されない`・`在庫切れの商品は在庫切れとして表示される`）
- 一覧から解除すると一覧から消える: 同上（`お気に入りを解除すると一覧から消える`）
- 登録が0件でも一覧を開ける: 同上（`お気に入りが無いときは空で返る`）
- 一覧の未ログイン時のリダイレクト: 同上（`未ログインではログイン画面へリダイレクトされる`）
- ボタンの表示切り替え（配色・ラベル）・一覧の0件表示: 自動テストなし。目視確認で担保する
