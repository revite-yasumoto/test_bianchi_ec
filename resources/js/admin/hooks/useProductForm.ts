import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { FormEventHandler } from 'react';
import type { NewImage } from '@/admin/Components/Product/ImageUploader';
import { newSpec, type SpecInput } from '@/admin/Components/Product/SpecEditor';
import { skuKey, useSkuMatrix, type SkuInput } from './useSkuMatrix';

export type ProductVariantData = {
    size_name: string | null;
    color_name: string | null;
    branch_code: string | null;
    is_available: boolean;
    quantity: number;
};

export type ProductFormData = {
    id: number;
    product_code: string;
    name: string;
    category_id: number;
    price: number;
    description: string | null;
    is_published: boolean;
    has_sku: boolean;
    images: { id: number; url: string }[];
    specs: { label: string; value: string }[];
    variants: ProductVariantData[];
};

type BasicFields = {
    product_code: string;
    name: string;
    category_id: string;
    price: string;
    description: string;
    is_published: boolean;
    has_sku: boolean;
};

let newImageUidCounter = 0;

/** 並び順を保ったまま重複を除く */
function uniqueValues(values: (string | null)[]): string[] {
    return values
        .filter((value): value is string => value !== null && value !== '')
        .filter((value, index, all) => all.indexOf(value) === index);
}

function initialSkuInputs(
    variants: ProductVariantData[],
): Record<string, SkuInput> {
    const inputs: Record<string, SkuInput> = {};

    for (const variant of variants) {
        if (variant.color_name === null || variant.size_name === null) {
            continue;
        }

        inputs[skuKey(variant.color_name, variant.size_name)] = {
            branch_code: variant.branch_code ?? '',
            quantity: variant.quantity,
            is_available: variant.is_available,
        };
    }

    return inputs;
}

export function useProductForm(product: ProductFormData | null) {
    const { data, setData, post, processing, errors, transform } =
        useForm<BasicFields>({
            product_code: product?.product_code ?? '',
            name: product?.name ?? '',
            category_id: product ? String(product.category_id) : '',
            price: product ? String(product.price) : '',
            description: product?.description ?? '',
            is_published: product?.is_published ?? false,
            has_sku: product?.has_sku ?? false,
        });

    const [colors, setColors] = useState<string[]>(() =>
        uniqueValues((product?.variants ?? []).map((v) => v.color_name)),
    );
    const [sizes, setSizes] = useState<string[]>(() =>
        uniqueValues((product?.variants ?? []).map((v) => v.size_name)),
    );
    const [singleQuantity, setSingleQuantity] = useState<string>(() =>
        String(
            product && !product.has_sku
                ? (product.variants[0]?.quantity ?? 0)
                : 0,
        ),
    );
    const [specs, setSpecs] = useState<SpecInput[]>(() =>
        (product?.specs ?? []).map((spec) => newSpec(spec.label, spec.value)),
    );
    const [existingImages, setExistingImages] = useState(product?.images ?? []);
    const [deletedImageIds, setDeletedImageIds] = useState<number[]>([]);
    const [newImages, setNewImages] = useState<NewImage[]>([]);

    const { rows, updateRow, toVariants } = useSkuMatrix({
        colors,
        sizes,
        productCode: data.product_code,
        initialInputs: useMemo(
            () => initialSkuInputs(product?.variants ?? []),
            [product],
        ),
    });

    // プレビュー用のオブジェクトURLはアンマウント時にまとめて解放する
    const newImagesRef = useRef(newImages);
    newImagesRef.current = newImages;

    useEffect(() => {
        return () => {
            for (const image of newImagesRef.current) {
                URL.revokeObjectURL(image.previewUrl);
            }
        };
    }, []);

    const addFiles = (files: File[]) => {
        const added = files.map((file) => {
            newImageUidCounter += 1;

            return {
                uid: `image-${newImageUidCounter}`,
                file,
                previewUrl: URL.createObjectURL(file),
            };
        });

        setNewImages((previous) => [...previous, ...added]);
    };

    const removeNewImage = (uid: string) => {
        setNewImages((previous) => {
            const target = previous.find((image) => image.uid === uid);

            if (target) {
                URL.revokeObjectURL(target.previewUrl);
            }

            return previous.filter((image) => image.uid !== uid);
        });
    };

    const removeExistingImage = (id: number) => {
        setExistingImages((previous) =>
            previous.filter((image) => image.id !== id),
        );
        setDeletedImageIds((previous) => [...previous, id]);
    };

    const buildVariants = (): ProductVariantData[] => {
        if (!data.has_sku) {
            return [
                {
                    size_name: null,
                    color_name: null,
                    branch_code: null,
                    is_available: true,
                    quantity: Number(singleQuantity || 0),
                },
            ];
        }

        return toVariants();
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        transform((fields) => ({
            ...fields,
            variants: buildVariants(),
            specs: specs.map(({ label, value }) => ({ label, value })),
            images: newImages.map((image) => image.file),
            deleted_image_ids: deletedImageIds,
            ...(product ? { _method: 'put' } : {}),
        }));

        post(
            product
                ? route('admin.products.update', [product.id])
                : route('admin.products.store'),
            { forceFormData: true },
        );
    };

    /** `variants.0.branch_code` のようなネストしたキーも受け取るため、キー名を限定しない形で公開する */
    const fieldErrors = errors as Record<string, string>;

    /**
     * 画像は枚数のルールが `images`、1枚ごとのルールが `images.0` のように別のキーで返る。
     * 後者だけが出たときに画面上どこにもエラーが出ないことがないよう、最初の1件を拾って併せて扱う。
     */
    const imageError =
        fieldErrors.images ??
        Object.entries(fieldErrors).find(([key]) =>
            key.startsWith('images.'),
        )?.[1];

    const setField = (field: keyof BasicFields, value: string | boolean) => {
        setData((previous) => ({ ...previous, [field]: value }));
    };

    return {
        data,
        setField,
        errors: fieldErrors,
        imageError,
        processing,
        submit,
        colors,
        setColors,
        sizes,
        setSizes,
        singleQuantity,
        setSingleQuantity,
        specs,
        setSpecs,
        existingImages,
        newImages,
        addFiles,
        removeNewImage,
        removeExistingImage,
        skuRows: rows,
        updateSkuRow: updateRow,
    };
}
