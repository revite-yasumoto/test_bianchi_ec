import { router, useForm } from '@inertiajs/react';

/**
 * カート明細1行の数量変更・削除を扱う。
 * 数量は明細ごとに独立して送るため、行コンポーネント単位でこのHookを使う。
 */
export function useCartItem(cartItemId: number, quantity: number) {
    const form = useForm({ quantity });

    const changeQuantity = (next: number) => {
        form.transform(() => ({ quantity: next }));
        form.put(route('cart.items.update', [cartItemId]), {
            preserveScroll: true,
        });
    };

    const remove = () => {
        router.delete(route('cart.items.destroy', [cartItemId]), {
            preserveScroll: true,
        });
    };

    return {
        changeQuantity,
        remove,
        isProcessing: form.processing,
        error: form.errors.quantity,
    };
}
