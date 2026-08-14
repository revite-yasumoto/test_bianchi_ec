import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { BasicInfoCard } from '@/admin/Components/Product/BasicInfoCard';
import { ImageUploader } from '@/admin/Components/Product/ImageUploader';
import { SkuTable } from '@/admin/Components/Product/SkuTable';
import { SkuToggle } from '@/admin/Components/Product/SkuToggle';
import { SpecEditor } from '@/admin/Components/Product/SpecEditor';
import { VariationEditor } from '@/admin/Components/Product/VariationEditor';
import {
    useProductForm,
    type ProductFormData,
} from '@/admin/hooks/useProductForm';

type Props = {
    product: ProductFormData | null;
    categories: { id: number; name: string }[];
    sizeOptions: string[];
    colorOptions: string[];
};

export default function Form({
    product,
    categories,
    sizeOptions,
    colorOptions,
}: Props) {
    const form = useProductForm(product);
    const { data, setField, errors } = form;
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);

    return (
        <AdminLayout title={product ? '商品編集' : '商品登録'}>
            <form
                onSubmit={form.submit}
                className="grid grid-cols-[repeat(auto-fit,minmax(340px,1fr))] items-start gap-4"
            >
                <div className="flex flex-col gap-4">
                    <BasicInfoCard
                        values={data}
                        categories={categories}
                        errors={errors}
                        onChange={setField}
                    />

                    <ImageUploader
                        existingImages={form.existingImages}
                        newImages={form.newImages}
                        error={form.imageError}
                        onAddFiles={form.addFiles}
                        onRemoveExisting={form.removeExistingImage}
                        onRemoveNew={form.removeNewImage}
                    />

                    <SpecEditor specs={form.specs} onChange={form.setSpecs} />
                </div>

                <div className="flex flex-col gap-4">
                    <div className="rounded-xl border border-admin-line bg-white p-5">
                        <div className="flex items-center gap-3">
                            <h2 className="text-[13px] font-extrabold text-admin-ink">
                                SKU（バリエーション）
                            </h2>
                            <SkuToggle
                                checked={data.has_sku}
                                onChange={(checked) =>
                                    setField('has_sku', checked)
                                }
                            />
                        </div>

                        {data.has_sku ? (
                            <div className="mt-4 flex flex-col gap-4">
                                <VariationEditor
                                    label="カラー"
                                    values={form.colors}
                                    options={colorOptions}
                                    onAdd={(value) =>
                                        form.setColors((previous) => [
                                            ...previous,
                                            value,
                                        ])
                                    }
                                    onRemove={(value) =>
                                        form.setColors((previous) =>
                                            previous.filter(
                                                (current) => current !== value,
                                            ),
                                        )
                                    }
                                />
                                <VariationEditor
                                    label="サイズ"
                                    values={form.sizes}
                                    options={sizeOptions}
                                    onAdd={(value) =>
                                        form.setSizes((previous) => [
                                            ...previous,
                                            value,
                                        ])
                                    }
                                    onRemove={(value) =>
                                        form.setSizes((previous) =>
                                            previous.filter(
                                                (current) => current !== value,
                                            ),
                                        )
                                    }
                                />
                            </div>
                        ) : (
                            <div className="mt-4">
                                <label
                                    htmlFor="single_quantity"
                                    className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                                >
                                    在庫数
                                </label>
                                <input
                                    id="single_quantity"
                                    type="number"
                                    min={0}
                                    value={form.singleQuantity}
                                    onChange={(event) =>
                                        form.setSingleQuantity(
                                            event.target.value,
                                        )
                                    }
                                    className="max-w-40 rounded-lg border border-admin-line px-3 py-2 text-base"
                                />
                                <p className="mt-2.5 text-[11.5px] leading-relaxed text-admin-ink-muted">
                                    SKUなしの商品は単一在庫で管理します。フロントでは「在庫あり／在庫切れ」の二値で表示されます。
                                </p>
                                {errors['variants.0.quantity'] ? (
                                    <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                        {errors['variants.0.quantity']}
                                    </p>
                                ) : null}
                            </div>
                        )}
                    </div>

                    {data.has_sku ? (
                        <SkuTable
                            rows={form.skuRows}
                            errors={errors}
                            onChange={form.updateSkuRow}
                        />
                    ) : null}

                    {errors.variants ? (
                        <p className="text-[11.5px] font-bold text-admin-danger">
                            {errors.variants}
                        </p>
                    ) : null}

                    <div className="flex gap-2.5">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="flex-1 rounded-lg bg-admin-brand py-3 text-[13.5px] font-extrabold text-white disabled:opacity-60"
                        >
                            保存する
                        </button>
                        <Link
                            href={route('admin.products.index')}
                            className="rounded-lg border border-admin-line bg-white px-5 py-3 text-[13px] font-bold text-admin-ink"
                        >
                            キャンセル
                        </Link>
                    </div>

                    {product ? (
                        <button
                            type="button"
                            className="self-start text-[12px] font-bold text-admin-danger"
                            onClick={() => setIsDeleteOpen(true)}
                        >
                            この商品を削除する
                        </button>
                    ) : null}
                </div>
            </form>

            {product ? (
                <ConfirmDialog
                    isOpen={isDeleteOpen}
                    title="商品の削除"
                    message={`「${product.name}」を削除します。商品画像・SKU・在庫も削除されます。過去の注文明細は削除されません。`}
                    confirmLabel="削除する"
                    confirmVariant="danger"
                    onConfirm={() =>
                        router.delete(
                            route('admin.products.destroy', [product.id]),
                        )
                    }
                    onCancel={() => setIsDeleteOpen(false)}
                />
            ) : null}
        </AdminLayout>
    );
}
