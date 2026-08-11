import { usePage } from '@inertiajs/react';
import { useEffect, useId, useRef } from 'react';
import { EmptyState } from '@/shared/Components/EmptyState';
import { NavLink } from './NavLink';

type CartDrawerProps = {
    isOpen: boolean;
    onClose: () => void;
};

/**
 * ヘッダーのカートボタンから開く右スライドインのドロワー。
 * 明細の描画は単位15（カート）でカート明細を受け取るようにして差し替える。
 */
export function CartDrawer({ isOpen, onClose }: CartDrawerProps) {
    const { cartCount } = usePage<FrontSharedProps>().props;
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
                    <EmptyState message="カートは空です" />
                </div>

                <div className="border-t border-line px-5 py-4">
                    <NavLink
                        item={{
                            key: 'cart',
                            label: 'カートを見る',
                            routeName: 'cart.index',
                        }}
                        className="block w-full rounded-full bg-coral py-3.5 text-center text-sm font-extrabold text-white"
                    />
                </div>
            </div>
        </>
    );
}
