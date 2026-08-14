import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

const TOAST_DURATION_MS = 2200;
/** エラーは読み終える前に消えないよう、成功より長く残す */
const ERROR_DURATION_MS = 5000;

type ToastState = {
    message: string;
    isError: boolean;
};

/**
 * 画面下中央のトースト。共有プロパティの `flash.success` / `flash.error` を監視し、
 * 値が入ったリクエストのたびに表示して自動で消える。
 */
export function Toast() {
    const { flash } = usePage<FrontSharedProps>().props;
    const [toast, setToast] = useState<ToastState | null>(null);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (!flash.success && !flash.error) {
            return;
        }

        const isError = !flash.success;

        setToast({
            message: isError ? (flash.error ?? '') : (flash.success ?? ''),
            isError,
        });

        timeoutRef.current = setTimeout(
            () => setToast(null),
            isError ? ERROR_DURATION_MS : TOAST_DURATION_MS,
        );

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [flash.success, flash.error]);

    if (!toast) {
        return null;
    }

    return (
        <div
            role={toast.isError ? 'alert' : 'status'}
            aria-live={toast.isError ? 'assertive' : 'polite'}
            className={cn(
                'fixed bottom-6 left-1/2 z-[80] max-w-[90vw] -translate-x-1/2 rounded-full px-6 py-3 text-[13px] font-bold text-white shadow-lg',
                toast.isError ? 'bg-coral' : 'bg-ink',
            )}
        >
            {toast.message}
        </div>
    );
}
