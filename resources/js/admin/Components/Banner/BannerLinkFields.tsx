import type { BannerOption } from './BannerManager';

type BannerLinkFieldsProps = {
    productId: string;
    linkUrl: string;
    productOptions: BannerOption[];
    errors: { product_id?: string; link_url?: string };
    onProductIdChange: (value: string) => void;
    onLinkUrlChange: (value: string) => void;
};

const LABEL_CLASS = 'mb-1.5 block text-sm font-bold text-admin-ink';

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const ERROR_CLASS = 'mt-1 text-sm font-bold text-admin-danger';

/**
 * 「見る」ボタンの遷移先を決める2項目。優先順位を説明する注記を両者の後にまとめて置く。
 */
export function BannerLinkFields({
    productId,
    linkUrl,
    productOptions,
    errors,
    onProductIdChange,
    onLinkUrlChange,
}: BannerLinkFieldsProps) {
    return (
        <>
            <div>
                <label htmlFor="banner_product_id" className={LABEL_CLASS}>
                    訴求商品
                </label>
                <select
                    id="banner_product_id"
                    value={productId}
                    className={FIELD_CLASS}
                    aria-invalid={errors.product_id !== undefined}
                    onChange={(event) => onProductIdChange(event.target.value)}
                >
                    <option value="">指定しない</option>
                    {productOptions.map((option) => (
                        <option key={option.id} value={String(option.id)}>
                            {option.label}
                        </option>
                    ))}
                </select>
                {errors.product_id ? (
                    <p className={ERROR_CLASS}>{errors.product_id}</p>
                ) : null}
            </div>

            <div>
                <label htmlFor="banner_link_url" className={LABEL_CLASS}>
                    リンク先（訴求商品を指定しないとき）
                </label>
                <input
                    id="banner_link_url"
                    type="text"
                    maxLength={255}
                    value={linkUrl}
                    placeholder="例：/products"
                    className={FIELD_CLASS}
                    aria-invalid={errors.link_url !== undefined}
                    onChange={(event) => onLinkUrlChange(event.target.value)}
                />
                {errors.link_url ? (
                    <p className={ERROR_CLASS}>{errors.link_url}</p>
                ) : null}
                <p className="mt-1.5 text-sm text-admin-ink-muted">
                    訴求商品を指定するとその商品詳細へ、指定しないときはリンク先へ移動します。どちらもなければ商品一覧へ移動します。
                </p>
            </div>
        </>
    );
}
