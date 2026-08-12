import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type FavoriteButtonProps = {
    productId: number;
    isFavorited: boolean;
};

/**
 * 未ログインでも押せる。サーバー側の `auth` ミドルウェアがログイン画面へ誘導し、
 * ログイン後に元の商品詳細へ戻る。
 */
export function FavoriteButton({
    productId,
    isFavorited,
}: FavoriteButtonProps) {
    const toggle = () => {
        if (isFavorited) {
            router.delete(route('favorites.destroy', [productId]), {
                preserveScroll: true,
            });

            return;
        }

        router.post(
            route('favorites.store'),
            { product_id: productId },
            { preserveScroll: true },
        );
    };

    return (
        <button
            type="button"
            aria-pressed={isFavorited}
            onClick={toggle}
            className={cn(
                'rounded-full border-[1.5px] px-5 py-4 text-sm font-bold',
                isFavorited
                    ? 'border-coral text-coral'
                    : 'border-line text-ink hover:border-brand',
            )}
        >
            {isFavorited ? 'お気に入り済' : 'お気に入りに追加'}
        </button>
    );
}
