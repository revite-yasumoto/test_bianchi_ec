import { useCallback, useMemo, useState } from 'react';

export type SkuRow = {
    /** `${color}|${size}`。カラー・サイズを増減しても入力値をこのキーで引き継ぐ */
    key: string;
    color_name: string;
    size_name: string;
    branch_code: string;
    /** 表示用。取扱対象外は `-`。保存時はサーバー側で組み立てる */
    sku_code: string;
    quantity: number;
    is_available: boolean;
};

export type SkuInput = {
    branch_code: string;
    quantity: number;
    is_available: boolean;
};

export type VariantPayload = {
    size_name: string | null;
    color_name: string | null;
    branch_code: string | null;
    is_available: boolean;
    quantity: number;
};

export function skuKey(color: string, size: string): string {
    return `${color}|${size}`;
}

/** 枝番の既定値。カラーの並び順×10 + サイズの並び順（モックと同じ規則） */
function defaultBranchCode(colorIndex: number, sizeIndex: number): string {
    return String((colorIndex + 1) * 10 + sizeIndex + 1);
}

type UseSkuMatrixParams = {
    colors: string[];
    sizes: string[];
    productCode: string;
    initialInputs?: Record<string, SkuInput>;
};

export function useSkuMatrix({
    colors,
    sizes,
    productCode,
    initialInputs = {},
}: UseSkuMatrixParams) {
    const [inputs, setInputs] =
        useState<Record<string, SkuInput>>(initialInputs);

    const rows = useMemo<SkuRow[]>(() => {
        return colors.flatMap((color, colorIndex) =>
            sizes.map((size, sizeIndex) => {
                const key = skuKey(color, size);
                const input = inputs[key];
                const branchCode =
                    input?.branch_code ??
                    defaultBranchCode(colorIndex, sizeIndex);
                const isAvailable = input?.is_available ?? true;

                return {
                    key,
                    color_name: color,
                    size_name: size,
                    branch_code: branchCode,
                    sku_code:
                        isAvailable && branchCode !== ''
                            ? `${productCode}-${branchCode}`
                            : '-',
                    quantity: input?.quantity ?? 0,
                    is_available: isAvailable,
                };
            }),
        );
    }, [colors, sizes, productCode, inputs]);

    const updateRow = useCallback((row: SkuRow, changes: Partial<SkuInput>) => {
        // row は inputs から生成済みのため、未入力の既定値を含めた現在値をそのままベースにする
        setInputs((previous) => ({
            ...previous,
            [row.key]: {
                branch_code: row.branch_code,
                quantity: row.quantity,
                is_available: row.is_available,
                ...changes,
            },
        }));
    }, []);

    const toVariants = useCallback(
        (): VariantPayload[] =>
            rows.map((row) => ({
                size_name: row.size_name,
                color_name: row.color_name,
                branch_code: row.is_available ? row.branch_code : null,
                is_available: row.is_available,
                quantity: row.is_available ? row.quantity : 0,
            })),
        [rows],
    );

    return { rows, updateRow, toVariants };
}
