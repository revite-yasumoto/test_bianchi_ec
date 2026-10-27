import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { Modal } from '@/shared/Components/Modal';
import { BannerImageField } from './BannerImageField';
import { BannerLinkFields } from './BannerLinkFields';
import type { BannerOption, BannerRow } from './BannerManager';

type BannerEditorModalProps = {
    /** 編集対象。新規作成のときは `null` */
    row: BannerRow | null;
    backgroundOptions: { value: string; label: string }[];
    productOptions: BannerOption[];
    onClose: () => void;
    onSaved: () => void;
};

type BannerForm = {
    tag: string;
    title: string;
    subtitle: string;
    background: string;
    product_id: string;
    link_url: string;
    sort_order: string;
    is_active: boolean;
    pc_image: File | null;
    sp_image: File | null;
    remove_pc_image: boolean;
    remove_sp_image: boolean;
};

const LABEL_CLASS = 'mb-1.5 block text-sm font-bold text-admin-ink';

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const ERROR_CLASS = 'mt-1 text-sm font-bold text-admin-danger';

export function BannerEditorModal({
    row,
    backgroundOptions,
    productOptions,
    onClose,
    onSaved,
}: BannerEditorModalProps) {
    const { data, setData, post, processing, errors, transform } =
        useForm<BannerForm>({
            tag: row?.tag ?? '',
            title: row?.title ?? '',
            subtitle: row?.subtitle ?? '',
            background: row?.background ?? backgroundOptions[0]?.value ?? '',
            product_id: row?.product_id ? String(row.product_id) : '',
            link_url: row?.link_url ?? '',
            sort_order: String(row?.sort_order ?? 0),
            is_active: row?.is_active ?? true,
            pc_image: null,
            sp_image: null,
            remove_pc_image: false,
            remove_sp_image: false,
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        // ファイルを送るため multipart にし、更新はメソッドスプーフィングで PUT として扱わせる
        transform((fields) => ({
            ...fields,
            ...(row ? { _method: 'put' } : {}),
        }));

        post(
            row
                ? route('admin.banners.update', [row.id])
                : route('admin.banners.store'),
            { forceFormData: true, preserveScroll: true, onSuccess: onSaved },
        );
    };

    return (
        <Modal
            isOpen
            title={row ? 'メインビジュアルを編集' : 'メインビジュアルを作成'}
            onClose={onClose}
            closeOnOverlayClick={false}
            className="max-w-[560px] rounded-xl p-6"
        >
            <form onSubmit={handleSubmit} className="flex flex-col gap-3">
                <div>
                    <label htmlFor="banner_tag" className={LABEL_CLASS}>
                        小ラベル
                    </label>
                    <input
                        id="banner_tag"
                        type="text"
                        required
                        maxLength={50}
                        value={data.tag}
                        placeholder="例：2026 ROAD COLLECTION"
                        className={FIELD_CLASS}
                        aria-invalid={errors.tag !== undefined}
                        onChange={(event) => setData('tag', event.target.value)}
                    />
                    {errors.tag ? (
                        <p className={ERROR_CLASS}>{errors.tag}</p>
                    ) : null}
                </div>

                <div>
                    <label htmlFor="banner_title" className={LABEL_CLASS}>
                        見出し
                    </label>
                    <textarea
                        id="banner_title"
                        rows={2}
                        required
                        maxLength={255}
                        value={data.title}
                        className={FIELD_CLASS}
                        aria-invalid={errors.title !== undefined}
                        onChange={(event) =>
                            setData('title', event.target.value)
                        }
                    />
                    {errors.title ? (
                        <p className={ERROR_CLASS}>{errors.title}</p>
                    ) : null}
                </div>

                <div>
                    <label htmlFor="banner_subtitle" className={LABEL_CLASS}>
                        サブ見出し
                    </label>
                    <input
                        id="banner_subtitle"
                        type="text"
                        maxLength={255}
                        value={data.subtitle}
                        className={FIELD_CLASS}
                        aria-invalid={errors.subtitle !== undefined}
                        onChange={(event) =>
                            setData('subtitle', event.target.value)
                        }
                    />
                    {errors.subtitle ? (
                        <p className={ERROR_CLASS}>{errors.subtitle}</p>
                    ) : null}
                </div>

                <div className="grid grid-cols-2 gap-2.5">
                    <div>
                        <label
                            htmlFor="banner_background"
                            className={LABEL_CLASS}
                        >
                            背景色
                        </label>
                        <select
                            id="banner_background"
                            value={data.background}
                            className={FIELD_CLASS}
                            aria-invalid={errors.background !== undefined}
                            onChange={(event) =>
                                setData('background', event.target.value)
                            }
                        >
                            {backgroundOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        {errors.background ? (
                            <p className={ERROR_CLASS}>{errors.background}</p>
                        ) : null}
                    </div>

                    <div>
                        <label
                            htmlFor="banner_sort_order"
                            className={LABEL_CLASS}
                        >
                            表示順
                        </label>
                        <input
                            id="banner_sort_order"
                            type="number"
                            required
                            min={0}
                            max={9999}
                            step={1}
                            value={data.sort_order}
                            className={FIELD_CLASS}
                            aria-invalid={errors.sort_order !== undefined}
                            onChange={(event) =>
                                setData('sort_order', event.target.value)
                            }
                        />
                        {errors.sort_order ? (
                            <p className={ERROR_CLASS}>{errors.sort_order}</p>
                        ) : null}
                    </div>
                </div>

                <BannerLinkFields
                    productId={data.product_id}
                    linkUrl={data.link_url}
                    productOptions={productOptions}
                    errors={errors}
                    onProductIdChange={(value) => setData('product_id', value)}
                    onLinkUrlChange={(value) => setData('link_url', value)}
                />

                <div>
                    <label htmlFor="banner_is_active" className={LABEL_CLASS}>
                        公開状態
                    </label>
                    <select
                        id="banner_is_active"
                        value={data.is_active ? 'published' : 'draft'}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            setData(
                                'is_active',
                                event.target.value === 'published',
                            )
                        }
                    >
                        <option value="published">公開</option>
                        <option value="draft">非公開</option>
                    </select>
                </div>

                <BannerImageField
                    id="banner_pc_image"
                    label="PC用画像（横長 21:9）"
                    previewClassName="aspect-[21/9] w-60"
                    currentUrl={row?.pc_image_url ?? null}
                    file={data.pc_image}
                    isRemoved={data.remove_pc_image}
                    error={errors.pc_image}
                    onSelect={(file) => setData('pc_image', file)}
                    onRemove={() =>
                        setData((fields) => ({
                            ...fields,
                            pc_image: null,
                            remove_pc_image: true,
                        }))
                    }
                />

                <BannerImageField
                    id="banner_sp_image"
                    label="スマホ用画像（縦長 4:5）"
                    previewClassName="aspect-[4/5] w-28"
                    currentUrl={row?.sp_image_url ?? null}
                    file={data.sp_image}
                    isRemoved={data.remove_sp_image}
                    error={errors.sp_image}
                    onSelect={(file) => setData('sp_image', file)}
                    onRemove={() =>
                        setData((fields) => ({
                            ...fields,
                            sp_image: null,
                            remove_sp_image: true,
                        }))
                    }
                />
                <p className="text-sm text-admin-ink-muted">
                    未登録の画面幅では背景色で表示します。
                </p>

                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-admin-brand py-3 text-[15px] font-extrabold text-white disabled:opacity-60"
                >
                    保存する
                </button>
            </form>
        </Modal>
    );
}
