# フロント共通レイアウト・共通UIコンポーネント

本ドキュメントはフロント（会員向け画面）の共通レイアウト（ヘッダー・フッター・SPメニュー・カートドロワー・トースト）、フロント／管理画面の双方から使う共通UIコンポーネント（`resources/js/shared/`）、およびフォント・デザイントークンの**正本**。単位13以降の各フロント画面の仕様書は、これらの構成・Props型を再掲せず本書へリンクする。

## 機能概要

- **対象画面・機能の目的:** フロントの全ページが共通で使うレイアウトとUIパーツ、および両画面共通のフォント・配色トークンを提供する。
- **URL / メソッド:** 該当なし（画面固有のルートを持たないレイアウト・コンポーネント群）。
- **アクセス権限・ミドルウェア:** `FrontLayout` は認証の有無を問わず利用される。表示内容はログイン状態で切り替わる。認証そのものは [docs/front/auth.md](auth.md) が正本。
- **本ドキュメントのスコープ:** レイアウト・共通UIコンポーネントの構成とProps、共有プロパティ（`FrontSharedProps`）、フォント構成、デザイントークン。各画面固有の実装は各単位の仕様書に委ねる。

## 使用テーブル

共有プロパティの件数表示に `cart_items`（数量の合計）と `favorites`（明細数）を参照する。定義は [docs/2_database.md](../2_database.md) が正本。

## ユーザーインターフェース仕様（ワイヤーフレーム）

```
PC幅（lg以上）:
+------------------------------------------------------------------+
| Bianchi  商品一覧 新着ニュース 買い物ガイド   お気に入り 3 [カート 5] |
| BICYCLE STORE  マイページ お問い合わせ                  ログアウト  |
+------------------------------------------------------------------+
|                        （各画面のmain）                            |
+------------------------------------------------------------------+
| Bianchi   | SHOPPING     | SUPPORT        | LEGAL                 |
| BICYCLE   | 商品一覧      | 買い物ガイド    | 特定商取引法に基づく表記 |
| STORE     | カート        | お問い合わせ    | プライバシーポリシー    |
| — DEMO    | マイページ    | 新着ニュース    | 利用規約               |
|           | お気に入り    | 重要なお知らせ  |                       |
| © 2026 Bianchi Demo Store. これはデモ用のダミーサイトです。          |
+------------------------------------------------------------------+
       [トースト（下部中央・2.2秒で自動消去）]

SP幅（lg未満）: ヘッダーのナビを隠し、右端のハンバーガー（≡）で開閉する
パネルにナビ項目とログアウトを縦積みで表示する。

カートドロワー（カートボタン押下時・カート投入直後に開く・右からスライドイン・幅 min(360px,86%)）:
+---------------------------+
| カート（5）             × |
+---------------------------+
| [画] 商品名                |
|      レッド / M ×2  ¥29,600|
| [画] 商品名                |
|      規格なし ×1     ¥3,200|
+---------------------------+
| 商品合計          ¥32,800 |
| あと ¥7,200 で送料無料     |
| [     カートを見る     ]  |
+---------------------------+
```

- 未ログイン時のヘッダー右側は「ログイン」「会員登録」の2ボタンになる（お気に入り・カート・ログアウトは表示しない）。カートとお気に入りは会員に紐づくため、ゲストは利用できない。
- ヘッダー・フッター・SPメニューの各リンクは、対応するルート名が存在する（`route().has()` が `true`）場合のみリンクとして機能し、それ以外は非活性表示（クリック不可・淡色）になる。現在はナビゲーションに載る全リンクのルートが実装済みで、非活性になる項目はない。
- カートドロワー・モーダルは `Escape` キーと背景クリックで閉じ、閉じたときに元のフォーカス位置へ戻る。

## インターフェース ＆ データロジック

### 共有プロパティ（`HandleInertiaRequests::share()`）

```ts
type FrontAuthUser = {
    id: number;
    member_code: string;
    name: string;
};

type CartDrawerItem = {
    id: number;
    name: string;
    variant_label: string;   // 例: レッド / M（規格を持たない商品は「規格なし」）
    quantity: number;
    line_total: number;
    image_url: string | null;
    category_name: string;
};

type FrontSharedProps = {
    auth: { user: FrontAuthUser | null };
    cartCount: number;
    cartItems: CartDrawerItem[];
    freeShippingThreshold: number | null;
    favoriteCount: number;
    flash: { success: string | null; error: string | null };
};
```

- `/admin/*` 以外のInertiaリクエストに共有される（管理画面側には `auth.user` キー自体が存在しない。管理画面向けの `auth.admin` は [docs/admin/auth.md](../admin/auth.md) が正本）。
- 共有する会員のカラムは allowlist とし、ヘッダー表示とマイページ導線が使う `id`・`member_code`・`name` のみに絞る。`email`・`tel`・`status`・パスワードハッシュ・`remember_token` は共有しない。
- `cartCount` はカート明細の**数量の合計**、`favoriteCount` はお気に入りの**明細数**。未ログイン時はいずれも `0`。
- `cartItems` はカートドロワーの描画に使う明細で、未ログイン時は空配列。表示に使う項目のみの allowlist とし、商品IDや在庫数は含めない。`line_total` は商品単価 × 数量。
- `freeShippingThreshold` はドロワーの送料無料案内に使う `ec_settings.free_shipping_threshold`。案内は明細があるときだけ出すため、カートが空（未ログインを含む）のときは `null`。
- `flash.success` / `flash.error` はセッションの `success` / `error` キーを渡す。`Toast` が監視して表示する。

### フォント

3書体を `laravel-vite-plugin/fonts` の `bunny()` でセルフホストする（`vite.config.js`）。ビルド時に woff2/woff を取得して `public/build/` へ出力するため、実行時に外部CDNへ接続しない。

| トークン | 書体 | ウェイト | 用途 |
|---|---|---|---|
| `--font-sans` | `Schibsted Grotesk` | 400 / 700 / 800 | ロゴ・英字見出し |
| `--font-jp` | `Zen Kaku Gothic New` | 400 / 500 / 700 / 900 | 日本語本文（既定） |
| `--font-mono` | `Space Mono` | 400 / 700 | 金額・コード・日付 |

日本語本文を既定にするため、`resources/views/app.blade.php` の `<body>` に `font-jp` を指定する（Tailwind の既定である `--font-sans` は英字書体のため）。この指定はフロント・管理画面の共通テンプレートであり、管理画面の本文書体も `Zen Kaku Gothic New` になる。

### デザイントークン（`resources/css/app.css` `@theme`）

フロント用。管理画面用の `--color-admin-*` は [docs/admin/common-layout.md](../admin/common-layout.md) が正本。

| トークン | 値 | 用途 |
|---|---|---|
| `--color-bg2` | `#f5f1ea` | セクション背景・サイドカード |
| `--color-ink` | `#232323` | 本文 |
| `--color-ink2` | `#5e6b77` | 補助テキスト |
| `--color-line` | `#e7dfd2` | 罫線・枠線 |
| `--color-brand` | `#2f6f86` | ブランド色（主要ボタン・リンク） |
| `--color-brand-deep` | `#274f60` | ロゴ・フッター背景 |
| `--color-amber` | `#e7a93a` | 強調 |
| `--color-coral` | `#e1664b` | 購入導線ボタン・削除・エラー |
| `--color-rose` | `#c25e86` | アクセント |
| `--color-teal` | `#3e9e8f` | 成功・在庫あり |
| `--color-notice-bg` | `#fdf3e3` | 重要なお知らせバーの背景 |
| `--color-notice-line` | `#f0dfbe` | 同バーの下罫線 |
| `--color-notice-ink` | `#7a5a1e` | 同バーの本文 |
| `--color-notice-ink-muted` | `#9a7c3e` | 同バーの補助テキスト |

基本背景（モックの `--bg` = `#FFFFFF`）はTailwind標準の `white` を使うためトークン化しない。

ステータス表示の文字色／背景色の対は、Tailwindのトークンでは表せないため `resources/js/shared/lib/tone.ts` に定数として持つ。

```ts
type Tone = { fg: string; bg: string };

TONE.positive  // 在庫あり・出荷済み       { fg: '#2b6f64', bg: '#E4F2EF' }
TONE.negative  // 在庫切れ・キャンセル      { fg: '#8a4030', bg: '#FBE7E1' }
TONE.info      // 新商品・受付中           { fg: '#2F6F86', bg: '#E7F0F4' }
TONE.warning   // お知らせ                { fg: '#B0521F', bg: '#FDF0E2' }
```

### `FrontLayout`（`resources/js/front/Layouts/FrontLayout.tsx`）

```ts
type FrontLayoutProps = {
    title: string;
    description?: string;
    children: ReactNode;
};
```

`title` は `<Head>` のタイトル、`description` は `<meta name="description">` に渡す。カートドロワーの開閉状態を保持し、`Header` の `onOpenCart` と `CartDrawer` を接続する。

配下のページからドロワーを開けるよう、`useCartDrawer()`（`openCart(): void` を返す）を Context として公開する。カート投入直後にドロワーを開く用途で使い、`FrontLayout` の子コンポーネント内でのみ呼べる。

### `Header`（`resources/js/front/Components/Header.tsx`）

```ts
type HeaderProps = { onOpenCart: () => void };
```

`usePage<FrontSharedProps>()` からログイン状態・カート点数・お気に入り件数を取得する。SPメニューの開閉状態を保持する。

### `MobileMenu`（`resources/js/front/Components/MobileMenu.tsx`）

```ts
type MobileMenuProps = { isOpen: boolean; onNavigate: () => void };
```

### `NavMenu`（`resources/js/front/Components/NavMenu.ts`）

ヘッダーの `NAV_MENU`（5項目）とフッターの `FOOTER_COLUMNS`（3カラム）を定義する。

```ts
type NavMenuItem = { key: string; label: string; routeName: string };
type FooterColumn = { key: string; heading: string; links: NavMenuItem[] };
```

| 配置 | 表示名 | `routeName` |
|---|---|---|
| ヘッダー | 商品一覧 / 新着ニュース / 買い物ガイド / マイページ / お問い合わせ | `products.index` / `news.index` / `guide` / `mypage.index` / `contact` |
| フッター SHOPPING | 商品一覧 / カート / マイページ / お気に入り | `products.index` / `cart.index` / `mypage.index` / `mypage.favorites` |
| フッター SUPPORT | 買い物ガイド / お問い合わせ / 新着ニュース / 重要なお知らせ | `guide` / `contact` / `news.index` / `notices.index` |
| フッター LEGAL | 特定商取引法に基づく表記 / プライバシーポリシー / 利用規約 | `legal.tokushoho` / `legal.privacy` / `legal.terms` |

各ルート名は、対応する単位がその名前でルートを実装することを前提に本書が定めたもの。単位13以降で異なる名前を採用する場合は、実装側で本テーブルと `NavMenu.ts` の両方を更新する。

### `NavLink`（`resources/js/front/Components/NavLink.tsx`）

```ts
type NavLinkProps = {
    item: NavMenuItem;
    className?: string;
    currentClassName?: string;
};
```

`route().has()` が `false` の項目は `<span aria-disabled="true">` として非活性表示にする。現在ページには `aria-current="page"` を付ける。

### `Footer`（`resources/js/front/Components/Footer.tsx`）

Props なし。`FOOTER_COLUMNS` を3カラムで描画する。

### `CartDrawer`（`resources/js/front/Components/CartDrawer.tsx`）

```ts
type CartDrawerProps = { isOpen: boolean; onClose: () => void };
```

共有プロパティの `cartItems` を明細として描画し、`line_total` の合計を「商品合計」に出す。件数表示は `cartCount`。明細が0件のときは `EmptyState` で「カートは空です」を表示する。明細のサムネイルは `ProductVisual` で描画する。商品合計の下には送料無料までの案内文を出す（文言は `resources/js/front/lib/freeShipping.ts` の `freeShippingMessage()` でカートページと共通化する。[docs/front/cart.md](cart.md) 参照）。「カートを見る」はカートページ（`cart.index`）へ遷移する。

### `Pagination`（`resources/js/front/Components/Pagination.tsx`）

```ts
type PaginationProps = {
    links: { url: string | null; label: string; active: boolean }[];
};
```

`paginate()` が返す `links` をそのまま渡す。`&laquo; Previous` / `Next &raquo;` は「前へ」「次へ」に置き換え、リンク先が無い項目は `aria-disabled="true"` の `<span>`、現在ページには `aria-current="page"` を付ける。リンクが3件以下（1ページのみ）のときは何も描画しない。管理画面の同名コンポーネントとは配色トークンが異なるため別実装とする。

### `categoryTint`（`resources/js/front/lib/tint.ts`）

```ts
function categoryTint(categoryName: string): string;   // CSS の linear-gradient 文字列
```

商品画像が未登録のときのプレースホルダー背景。カテゴリ名に対応するグラデーションを返し、未登録のカテゴリ名にはカテゴリ名から決まる1色を返す（同じカテゴリでは常に同じ配色になる）。

### `CategorySilhouette`（`resources/js/front/Components/Product/CategorySilhouette.tsx`）

```ts
type CategorySilhouetteProps = {
    categoryName: string;
    className?: string;
};
```

カテゴリに対応する商材のシルエットを描くインラインSVG。`categoryTint` の背景の上に重ねて、画像未登録のプレースホルダーを商材の分かる見た目にする。図案はカテゴリ名で切り替え、未登録のカテゴリ名には汎用の自転車を返す。

| カテゴリ | 図案 |
|---|---|
| ロードバイク | ドロップハンドルの細身フレーム |
| MTB | 太いブロックタイヤとサスペンション |
| シティ | カゴと泥除けを備えたアップライトな車体 |
| eバイク | ダウンチューブにバッテリーを備えた車体 |
| パーツ | ボトルとボトルケージ |
| アパレル | 半袖ジャージ |

- 色は単色で、背景のグラデーションに対して白の低い不透明度で重ねる。装飾のため `aria-hidden="true"` を付け、読み上げの対象にしない。
- ラスター画像を持たないため、追加のHTTPリクエストが発生せず、拡大しても劣化しない。

### `ProductVisual`（`resources/js/front/Components/Product/ProductVisual.tsx`）

```ts
type ProductVisualProps = {
    imageUrl: string | null;
    categoryName: string;
    /** 画像が無いときに左下へ出す商品識別コード。省略すると出さない */
    productCode?: string;
    /** 画像の代替テキスト。周囲に商品名がある文脈では省略し、装飾扱いにする */
    alt?: string;
    /** 既定は `lazy`。ファーストビューに入る画像には `eager` を渡す */
    loading?: 'lazy' | 'eager';
    className?: string;
};
```

商品画像の表示とプレースホルダーの出し分けを1箇所に集約したコンポーネント。`imageUrl` があれば `<img>` を、無ければ `categoryTint` の背景に `CategorySilhouette` を重ね、`productCode` を渡した場合は左下に小さく併記する。商品カード・商品詳細のギャラリー・カート明細・カートドロワー・注文確認・注文詳細・TOPの閲覧履歴が使う。

商品詳細のメイン画像はファーストビューに入るため `eager` を渡す。それ以外の呼び出し元は既定の `lazy` を使う。

### 共通UIコンポーネント（`resources/js/shared/Components/`）

```ts
// Button — variant: primary(brand) / cta(coral) / outline / ghost
type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & { variant?: ButtonVariant };

// FormField — TextInput / SelectInput / TextareaInput が共通で使うラベル・エラーの枠
type FormFieldProps = {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    className?: string;
};

// TextInput / SelectInput / TextareaInput
type TextInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'id'> & FormFieldProps;
type SelectInputProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'id'> & FormFieldProps;
type TextareaInputProps = Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'id'> & FormFieldProps;

// Checkbox — モックの角丸カスタム表示に合わせ、ネイティブのinputを sr-only にして隣接spanで描画する
type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    children: ReactNode;
    error?: string;
};

// Badge — 配色は Tailwind のトークン外のため tone の実値を style で当てる
type BadgeProps = { tone: Tone; className?: string; children: ReactNode };

// Modal — 背景クリック・Escape で閉じる。内容が画面より高いときは背景側がスクロールし、
//         収まるときは中央に置く（入力欄の多いモーダルで下端のボタンに届かなくなるのを防ぐ）
type ModalProps = {
    isOpen: boolean;
    title: string;
    onClose: () => void;
    className?: string;
    children: ReactNode;
};

// EmptyState
type EmptyStateProps = { message: string; className?: string };
```

`Toast`（`resources/js/shared/Components/Toast.tsx`）は Props なし。`flash.success` / `flash.error` を監視して画面下中央に表示し、自動で消える。成功は `ink` 背景で2200ms、エラーは `coral` 背景・`role="alert"` で5000ms（読み終える前に消えないよう長くする）。両方に値がある場合は成功を優先する。

`yen(amount: number): string`（`resources/js/shared/lib/yen.ts`）は金額を `¥12,345` 形式に整形する。

## 業務ルール

- ナビゲーションに載せるリンクは、押しても遷移しない状態のまま残さない（対応するルートが無い間は非活性表示にする）。この扱いは、今後リンクを先行して追加する場合にも適用する。
- フロント（会員向け）はレスポンシブ対応する。ヘッダーのナビは `lg`（1024px）未満でハンバーガーメニューへ切り替える。モックのSP切り替え幅は900pxだが、独自ブレークポイントを追加せずTailwind標準の `lg` を使う。
- 入力要素のフォントサイズは16px以上にする（iOS Safariの自動ズーム防止）。

## 関連ドキュメント

- [docs/front/auth.md](auth.md) — 会員登録・ログイン・ログアウトの正本。共通レイアウトを使う最初の画面
- [docs/front/product-index.md](product-index.md) — 商品一覧。商品カードとページ送りを使う
- [docs/front/product-show.md](product-show.md) — 商品詳細。カート投入時に `useCartDrawer()` でドロワーを開く
- [docs/front/cart.md](cart.md) — カートページ。ドロワーの「カートを見る」の遷移先。送料無料の案内文言を共有する
- [docs/admin/common-layout.md](../admin/common-layout.md) — 管理画面共通レイアウトの正本。`--color-admin-*` トークンはこちらが正本
- [docs/1_system_overview.md](../1_system_overview.md) — ブランド表記・技術構成の前提
- [docs/2_database.md](../2_database.md) — `cart_items`・`favorites` テーブル定義の正本

## ソースファイル

| 種別 | パス |
|---|---|
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` |
| ビルド設定 | `vite.config.js` |
| テンプレート | `resources/views/app.blade.php` |
| スタイル | `resources/css/app.css` |
| Layout | `resources/js/front/Layouts/FrontLayout.tsx` |
| Component | `resources/js/front/Components/Header.tsx` |
| Component | `resources/js/front/Components/MobileMenu.tsx` |
| Component | `resources/js/front/Components/Footer.tsx` |
| Component | `resources/js/front/Components/CartDrawer.tsx` |
| Component | `resources/js/front/Components/NavMenu.ts` |
| Component | `resources/js/front/Components/NavLink.tsx` |
| Component | `resources/js/front/Components/Pagination.tsx` |
| Component | `resources/js/front/Components/Product/ProductVisual.tsx` |
| Component | `resources/js/front/Components/Product/CategorySilhouette.tsx` |
| ユーティリティ | `resources/js/front/lib/tint.ts` |
| 共通UI | `resources/js/shared/Components/Button.tsx` |
| 共通UI | `resources/js/shared/Components/FormField.tsx` |
| 共通UI | `resources/js/shared/Components/TextInput.tsx` |
| 共通UI | `resources/js/shared/Components/SelectInput.tsx` |
| 共通UI | `resources/js/shared/Components/TextareaInput.tsx` |
| 共通UI | `resources/js/shared/Components/Checkbox.tsx` |
| 共通UI | `resources/js/shared/Components/Badge.tsx` |
| 共通UI | `resources/js/shared/Components/Modal.tsx` |
| 共通UI | `resources/js/shared/Components/Toast.tsx` |
| 共通UI | `resources/js/shared/Components/EmptyState.tsx` |
| ユーティリティ | `resources/js/shared/lib/yen.ts` |
| ユーティリティ | `resources/js/shared/lib/tone.ts` |
| 型 | `resources/js/types/global.d.ts` |
| Test | `tests/Feature/Front/SharedPropsTest.php` |
| Test | `tests/Feature/Front/NavigationLinkTest.php` |

## 受け入れ条件

- 共有データの会員情報が `id`・`member_code`・`name` のみで、メールアドレス・パスワードハッシュ・電話番号・ステータスを含まない: `tests/Feature/Front/SharedPropsTest.php`（`共有データの会員情報は識別子・会員番号・氏名のみを含む`）
- 未ログイン時は `auth.user` が `null` で件数が0になる: 同上（`未ログイン時の共有データは会員情報を持たず件数が0になる`）
- `cartCount` が数量の合計、`favoriteCount` が明細数で共有される: 同上（`カート件数は数量の合計・お気に入り件数は明細数で共有される`）
- `cartItems` にカートドロワー用の明細（商品名・バリエーション名・数量・小計・カテゴリ名）と送料無料しきい値が共有される: 同上（`カートドロワー用の明細が共有される`）
- カートが空のときは送料無料しきい値が `null` になる: 同上（`カートが空のときは送料無料のしきい値を共有しない`）
- `auth.admin` はフロントのパスに共有されない: 同上（`管理画面の共有データはフロントのパスに現れない`）
- ヘッダー・フッターのリンク先ルートがすべて定義され、表示できる（リンク切れの検出）: `tests/Feature/Front/NavigationLinkTest.php`（未ログインで開ける8ルートと、ログインが必要な3ルート）
- 画像未登録の商品にカテゴリ別のシルエットと商品識別コードが出て、画像登録済みの商品には画像が出る: 自動テストなし。目視確認で担保する
- 未実装リンクが非活性表示になる（現在、該当する項目はない）: 自動テストなし。目視確認で担保する
- SP幅でハンバーガーメニューが開閉し、PC幅でナビが横並びになる: 自動テストなし。目視確認で担保する
- カートドロワーの開閉（背景クリック・`Escape`キー・閉じるボタン）とフォーカス復帰: 自動テストなし。目視確認で担保する
- トーストの表示位置・配色と自動消去（成功2.2秒／エラー5秒）: 自動テストなし。目視確認で担保する
- 3書体（`Schibsted Grotesk` / `Zen Kaku Gothic New` / `Space Mono`）が適用される: 自動テストなし。目視確認で担保する
- Props型定義の整合性: `npx tsc --noEmit`
