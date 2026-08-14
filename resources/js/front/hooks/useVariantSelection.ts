import { useState } from 'react';

export type VariantData = {
    id: number;
    size_name: string | null;
    color_name: string | null;
    sku_code: string | null;
    /** 取扱対象（規格の組み合わせとして販売する）かどうか */
    is_available: boolean;
    in_stock: boolean;
};

export type VariantOption = {
    name: string;
    selected: boolean;
    /** 取扱対象外または在庫切れで選択できない */
    disabled: boolean;
};

type Product = {
    has_sku: boolean;
    variants: VariantData[];
    sizes: string[];
    colors: string[];
};

/**
 * 選択中のカラー・サイズから購入可能なバリエーションを解決する。
 * 選択肢の活性判定は、その組み合わせが取扱対象かつ在庫ありであることを条件とする。
 */
export function useVariantSelection({
    has_sku: hasSku,
    variants,
    sizes,
    colors,
}: Product) {
    const [color, setColor] = useState<string | null>(null);
    const [size, setSize] = useState<string | null>(null);

    // カラーのみ／サイズのみの商品でも同じ判定に載せるため、空の軸は null 1件として扱う
    const sizeKeys: (string | null)[] = sizes.length > 0 ? sizes : [null];
    const colorKeys: (string | null)[] = colors.length > 0 ? colors : [null];

    const findVariant = (
        targetSize: string | null,
        targetColor: string | null,
    ): VariantData | undefined =>
        variants.find(
            (variant) =>
                variant.size_name === targetSize &&
                variant.color_name === targetColor,
        );

    const isBuyable = (
        targetSize: string | null,
        targetColor: string | null,
    ): boolean => {
        const variant = findVariant(targetSize, targetColor);

        return Boolean(variant?.is_available && variant.in_stock);
    };

    const colorOptions: VariantOption[] = colors.map((name) => ({
        name,
        selected: name === color,
        disabled: sizeKeys.every((sizeKey) => !isBuyable(sizeKey, name)),
    }));

    const sizeOptions: VariantOption[] = sizes.map((name) => ({
        name,
        selected: name === size,
        disabled: color
            ? !isBuyable(name, color)
            : colorKeys.every((colorKey) => !isBuyable(name, colorKey)),
    }));

    const selectColor = (nextColor: string) => {
        setColor(nextColor);

        // 選択中のサイズがそのカラーで購入できなくなる場合は選び直させる
        if (size && !isBuyable(size, nextColor)) {
            setSize(null);
        }
    };

    const isSelectionComplete =
        !hasSku ||
        ((colors.length === 0 || color !== null) &&
            (sizes.length === 0 || size !== null));

    const selectedVariant = isSelectionComplete
        ? (findVariant(
              sizes.length === 0 ? null : size,
              colors.length === 0 ? null : color,
          ) ?? null)
        : null;

    return {
        color,
        size,
        colorOptions,
        sizeOptions,
        selectColor,
        selectSize: setSize,
        isSelectionComplete,
        selectedVariant,
    };
}
