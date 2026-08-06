# 単位04: 管理画面 - カテゴリ管理・規格管理

依存: 単位03

商品登録の選択肢を供給するマスタ2画面。商品管理（単位05）より先に実装する。

## スコープ

- カテゴリ管理: 一覧（登録商品件数付き）・追加・削除
- 規格管理: サイズ・カラーをそれぞれ一覧・追加・削除
- 編集（名称変更）は要件に記載がないため実装しない（追加／削除のみ）

## 適用する既存スキル

| タイミング | スキル |
|---|---|
| `.php`（Controller / FormRequest） | `laravel-set:laravel` |
| クエリビルダを含む処理 | `db-mysql:mysql` |
| `.tsx` | `inertia-react:react` |
| JSX のマークアップ | `core:html` |
| Tailwind のクラス指定 | `core:css` |
| テスト作成 | `laravel-set:testing-laravel` |
| 実装完了後 | `core:code-styling` |
| 本単位の仕様書作成 | `core:specification-writer`（作成モード） |

## ルーティング（`routes/web.php` の `admin.` グループ内）

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/admin/categories` | `admin.categories.index` | カテゴリ管理 |
| POST | `/admin/categories` | `admin.categories.store` | カテゴリ追加 |
| DELETE | `/admin/categories/{category}` | `admin.categories.destroy` | カテゴリ削除 |
| GET | `/admin/spec-options` | `admin.spec-options.index` | 規格管理 |
| POST | `/admin/spec-options` | `admin.spec-options.store` | 規格追加 |
| DELETE | `/admin/spec-options/{specOption}` | `admin.spec-options.destroy` | 規格削除 |

## 新規作成・修正ファイル一覧

### バックエンド

| 種別 | パス | 内容 |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/CategoryController.php` | `index()` / `store()` / `destroy()` |
| Controller | `app/Http/Controllers/Admin/SpecOptionController.php` | `index()` / `store()` / `destroy()` |
| FormRequest | `app/Http/Requests/Admin/Category/StoreCategoryRequest.php` | 名称の必須・一意 |
| FormRequest | `app/Http/Requests/Admin/SpecOption/StoreSpecOptionRequest.php` | 種別・名称の必須・組み合わせの一意 |
| ルート | `routes/web.php` | **修正**: 上表のルートを追加 |

### フロントエンド

| 種別 | パス | 内容 |
|---|---|---|
| Page | `resources/js/admin/Pages/Category/Index.tsx` | カテゴリ一覧（名称・登録商品件数・削除）＋追加フォーム |
| Page | `resources/js/admin/Pages/SpecOption/Index.tsx` | サイズ・カラーの2カード横並び。各カードに一覧＋追加フォーム |
| Component | `resources/js/admin/Components/MasterListCard.tsx` | 白カード＋説明文＋行リスト＋末尾の追加フォーム（両画面で共用） |
| Component | `resources/js/admin/Components/Sidebar.tsx` | **修正**: 「規格管理」「カテゴリ管理」のリンクを有効化 |

## インターフェース ＆ データロジック

### Props の型

```ts
// Category/Index.tsx
type CategoryRow = { id: number; name: string; product_count: number };
type Props = { categories: CategoryRow[] };

// SpecOption/Index.tsx
type SpecOptionRow = { id: number; name: string };
type Props = { sizes: SpecOptionRow[]; colors: SpecOptionRow[] };
```

### バリデーション

`StoreCategoryRequest`:

| 項目 | ルール |
|---|---|
| `name` | 必須・文字列・最大50・`categories.name` で一意 |

`StoreSpecOptionRequest`:

| 項目 | ルール |
|---|---|
| `type` | 必須・`App\Enums\SpecOptionType` の値のいずれか |
| `name` | 必須・文字列・最大50・`(type, name)` の組み合わせで一意 |

### 主要な処理フロー

**カテゴリ一覧**: `categories` を `sort_order` → `id` 順に取得し、`withCount('products')` で登録商品件数を付与する。

**カテゴリ追加**: バリデーション → `sort_order` に現在の最大値+1 を設定して作成 → 一覧へリダイレクトし「カテゴリを追加しました」をフラッシュ

**カテゴリ削除**:
1. そのカテゴリに紐づく商品が1件以上ある場合は削除せず、「このカテゴリには商品が登録されているため削除できません」をエラーとして返す（`products.category_id` は `ON DELETE RESTRICT`）
2. 0件なら削除 → 「カテゴリを削除しました」をフラッシュ
3. 削除の実行前に確認モーダルを表示する

**規格追加／削除**: カテゴリと同様。規格の削除は、その規格値を使っている `product_variants` があっても**許可する**（`product_variants` は規格値を文字列として保持しており外部キーではないため、既存商品のSKUは壊れない）。ただし削除の確認モーダルで「既に登録済みの商品のSKUには影響しません」を明示する。

## 業務ルール

- カテゴリ・規格の並び順は `sort_order` の昇順、同値なら `id` の昇順。並び替えのUIは実装しない（要件に記載なし）
- 規格の削除は既存商品のSKUに影響しない。商品登録画面での「ワンクリック追加」の選択肢から消えるだけである

## テスト方針

`laravel-set:testing-laravel` を適用して作成する。

| テストファイル | 担保する内容 |
|---|---|
| `tests/Feature/Admin/Category/CategoryIndexTest.php` | 一覧に登録商品件数が正しく出ること／未認証は `admin.login` にリダイレクトされること |
| `tests/Feature/Admin/Category/CategoryStoreTest.php` | 正常追加／名称の重複を弾くこと／最大長超過を弾くこと |
| `tests/Feature/Admin/Category/CategoryDestroyTest.php` | 商品0件のカテゴリは削除できること／商品が紐づくカテゴリは削除できずエラーになること |
| `tests/Feature/Admin/SpecOption/SpecOptionStoreTest.php` | サイズ・カラーの追加／同種別・同名の重複を弾くこと／別種別の同名は許可すること |
| `tests/Feature/Admin/SpecOption/SpecOptionDestroyTest.php` | 削除できること／既存の `product_variants` が壊れないこと |

## 前提・論点（ユーザーの判断が必要な事項）

実装時はいずれも「推奨」の方針で進めてよい。

1. **カテゴリ名・規格名の変更**: 要件は追加／削除のみ。推奨=**編集は実装しない**。
2. **商品が紐づくカテゴリの削除**: 推奨=**拒否する**（注文明細はカテゴリ名をスナップショットで保持しているため過去注文は壊れないが、公開中の商品がカテゴリを失う状態を作らない）。
3. **並び替えUI**: 実装しない。
4. **削除の確認モーダル**: 要件は「検索がある全画面にクリアボタン」「在庫・ステータス更新に確認アラート」を求めている。マスタ削除は不可逆なため、推奨=**確認モーダルを表示する**。

## 完了時に行うこと

- `core:specification-writer`（作成モード）で以下を作成する
  - `docs/admin/category.md`
  - `docs/admin/spec-option.md`
- 本計画ファイルを削除し、トラッカーの状態を更新する
