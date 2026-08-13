import { CategorySilhouette } from '@/front/Components/Product/CategorySilhouette';
import { categoryTint } from '@/front/lib/tint';
import { cn } from '@/lib/utils';

type ProductVisualProps = {
    imageUrl: string | null;
    categoryName: string;
    /** 渡すと画像未登録のときに左下へ小さく併記する */
    productCode?: string;
    /** 画像の代替テキスト。周囲に商品名がある文脈では省略して装飾扱いにする */
    alt?: string;
    /** ファーストビューに入る画像は `eager` にする（商品詳細のメイン画像など） */
    loading?: 'lazy' | 'eager';
    className?: string;
};

/**
 * 商品画像とプレースホルダーの出し分けを集約する。
 * 画像が未登録の商品でも商材が伝わるよう、カテゴリ別の配色にシルエットを重ねる。
 */
export function ProductVisual({
    imageUrl,
    categoryName,
    productCode,
    alt = '',
    loading = 'lazy',
    className,
}: ProductVisualProps) {
    if (imageUrl) {
        return (
            <img
                src={imageUrl}
                alt={alt}
                loading={loading}
                className={cn('h-full w-full object-cover', className)}
            />
        );
    }

    return (
        <div
            style={{ backgroundImage: categoryTint(categoryName) }}
            className={cn(
                'relative flex h-full w-full items-center justify-center',
                className,
            )}
        >
            <CategorySilhouette
                categoryName={categoryName}
                className="w-3/5 text-white/35"
            />
            {productCode ? (
                <span className="absolute bottom-2 left-2.5 font-mono text-[10px] tracking-[.1em] text-white/80">
                    {productCode}
                </span>
            ) : null}
        </div>
    );
}
