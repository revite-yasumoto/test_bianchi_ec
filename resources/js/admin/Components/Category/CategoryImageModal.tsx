import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';
import { useFilePreview } from '@/admin/hooks/useFilePreview';
import { IMAGE_ACCEPT } from '@/admin/lib/imageUpload';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';
import { Modal } from '@/shared/Components/Modal';

export type CategoryImageTarget = {
    id: number;
    name: string;
    image_url: string | null;
};

type CategoryImageModalProps = {
    category: CategoryImageTarget;
    onClose: () => void;
};

export function CategoryImageModal({
    category,
    onClose,
}: CategoryImageModalProps) {
    const { showToast } = useAdminToast();
    const [isDeleting, setIsDeleting] = useState(false);
    const { data, setData, post, processing, errors, transform } = useForm<{
        image: File | null;
    }>({ image: null });
    const previewUrl = useFilePreview(data.image);
    const shownUrl = previewUrl ?? category.image_url;

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        // ファイルを送るため multipart にし、メソッドスプーフィングで PUT として扱わせる
        transform((fields) => ({ ...fields, _method: 'put' }));

        post(route('admin.categories.image.update', [category.id]), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                showToast('画像を保存しました');
            },
        });
    };

    const handleDelete = () => {
        setIsDeleting(true);

        router.delete(route('admin.categories.image.destroy', [category.id]), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                showToast('画像を削除しました');
            },
            onFinish: () => setIsDeleting(false),
        });
    };

    return (
        <Modal
            isOpen
            title={`「${category.name}」の画像`}
            onClose={onClose}
            className="max-w-[440px] rounded-xl p-6"
        >
            <form onSubmit={handleSubmit} className="flex flex-col gap-3">
                <p className="text-sm text-admin-ink-muted">
                    TOPのカテゴリ入口カードに表示します。未登録の場合は既定の配色とシルエットで表示します。
                </p>

                <div className="aspect-4/3 overflow-hidden rounded-lg border border-admin-line bg-admin-bg">
                    {shownUrl ? (
                        <img
                            src={shownUrl}
                            alt={
                                previewUrl
                                    ? '選択した画像のプレビュー'
                                    : '登録済みの画像'
                            }
                            className="h-full w-full object-cover"
                        />
                    ) : null}
                </div>

                <div>
                    <label
                        htmlFor="category_image"
                        className="mb-1.5 block text-sm font-bold text-admin-ink"
                    >
                        画像ファイル
                    </label>
                    <input
                        id="category_image"
                        type="file"
                        required
                        accept={IMAGE_ACCEPT}
                        aria-invalid={errors.image !== undefined}
                        className="text-sm"
                        onChange={(event) =>
                            setData('image', event.target.files?.[0] ?? null)
                        }
                    />
                    {errors.image ? (
                        <p className="mt-1 text-sm font-bold text-admin-danger">
                            {errors.image}
                        </p>
                    ) : null}
                </div>

                <div className="flex gap-2.5">
                    <button
                        type="submit"
                        disabled={processing || isDeleting}
                        className="flex-1 rounded-lg bg-admin-brand py-3 text-[15px] font-extrabold text-white disabled:opacity-60"
                    >
                        保存する
                    </button>
                    {category.image_url ? (
                        <button
                            type="button"
                            disabled={processing || isDeleting}
                            className="rounded-lg border border-admin-danger px-4 py-3 text-[15px] font-bold text-admin-danger disabled:opacity-60"
                            onClick={handleDelete}
                        >
                            画像を削除
                        </button>
                    ) : null}
                </div>
            </form>
        </Modal>
    );
}
