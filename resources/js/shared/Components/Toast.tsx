import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const TOAST_DURATION_MS = 2200;

/**
 * 画面下中央のトースト。共有プロパティの `flash.success` を監視し、
 * 値が入ったリクエストのたびに表示して自動で消える。
 */
export function Toast() {
    const { flash } = usePage<FrontSharedProps>().props;
    const [message, setMessage] = useState<string | null>(null);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (!flash.success) {
            return;
        }

        setMessage(flash.success);

        timeoutRef.current = setTimeout(
            () => setMessage(null),
            TOAST_DURATION_MS,
        );

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [flash.success]);

    if (!message) {
        return null;
    }

    return (
        <div
            role="status"
            aria-live="polite"
            className="fixed bottom-6 left-1/2 z-[80] -translate-x-1/2 rounded-full bg-ink px-6 py-3 text-[13px] font-bold text-white shadow-lg"
        >
            {message}
        </div>
    );
}
