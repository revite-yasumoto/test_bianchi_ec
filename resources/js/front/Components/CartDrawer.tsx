import { usePage } from '@inertiajs/react';
import { useEffect, useId, useRef } from 'react';
import { freeShippingMessage } from '@/front/lib/freeShipping';
import { categoryTint } from '@/front/lib/tint';
import { EmptyState } from '@/shared/Components/EmptyState';
import { yen } from '@/shared/lib/yen';
import { NavLink } from './NavLink';

type CartDrawerProps = {
    isOpen: boolean;
    onClose: () => void;
};

/** ヘッダーのカートボタン、およびカート投入直後に開く右スライドインのドロワー */
export function CartDrawer({ isOpen, onClose }: CartDrawerProps) {
    const { cartCount, cartItems, freeShippingThreshold } =
        usePage<FrontSharedProps>().props;
    const itemsTotal = cartItems.reduce(
        (total, item) => total + item.line_total,
        0,
    );
    const titleId = useId();
    const drawerRef = useRef<HTMLDivElement>(null);
    const previouslyFocusedRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        previouslyFocusedRef.current =
            document.activeElement as HTMLElement | null;
        drawerRef.current?.focus();

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            previouslyFocusedRef.current?.focus();
        };
    }, [isOpen, onClose]);

    if (!isOpen) {
        return null;
    }

    return (
        <>
            <div
                className="fixed inset-0 z-[70] bg-ink/45"
                onClick={onClose}
                aria-hidden="true"
            />
            <div
                ref={drawerRef}
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                className="fixed inset-y-0 right-0 z-[71] flex w-[min(360px,86%)] flex-col bg-white shadow-2xl outline-none"
            >
                <div className="flex items-center border-b border-line px-5 py-4">
                    <h2 id={titleId} className="text-[15px] font-extrabold">
                        カート（{cartCount}）
                    </h2>
                    <button
                        type="button"
                        aria-label="閉じる"
                        onClick={onClose}
                        className="ml-auto text-xl text-ink2"
                    >
                        ×
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-5">
                    {cartItems.length > 0 ? (
                        <ul>
                            {cartItems.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex gap-3 border-b border-line py-3.5"
                                >
                                    <div
                                        className="h-14 w-14 shrink-0 overflow-hidden rounded-[10px]"
                                        style={{
                                            backgroundImage: categoryTint(
                                                item.category_name,
                                            ),
                                        }}
                                    >
                                        {item.image_url ? (
                                            <img
                                                src={item.image_url}
                                                alt=""
                                                loading="lazy"
                                                className="h-full w-full object-cover"
                                            />
                                        ) : null}
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-[12.5px] leading-[1.45] font-bold">
                                            {item.name}
                                        </p>
                                        <p className="mt-0.5 text-[11px] text-ink2">
                                            {item.variant_label} ×
                                            {item.quantity}
                                        </p>
                                    </div>
                                    <p className="font-mono text-[12.5px] font-bold">
                                        {yen(item.line_total)}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState message="カートは空です" />
                    )}
                </div>

                <div className="border-t border-line px-5 py-4">
                    <div className="flex justify-between text-sm font-extrabold">
                        <span>商品合計</span>
                        <span className="font-mono">{yen(itemsTotal)}</span>
                    </div>
                    {freeShippingThreshold !== null ? (
                        <p className="mt-1.5 text-[11.5px] text-ink2">
                            {freeShippingMessage(
                                freeShippingThreshold - itemsTotal,
                            )}
                        </p>
                    ) : null}
                    <NavLink
                        item={{
                            key: 'cart',
                            label: 'カートを見る',
                            routeName: 'cart.index',
                        }}
                        className="mt-3.5 block w-full rounded-full bg-coral py-3.5 text-center text-sm font-extrabold text-white"
                    />
                </div>
            </div>
        </>
    );
}
