import { useEffect, useId, useRef } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type ModalProps = {
    isOpen: boolean;
    title: string;
    onClose: () => void;
    className?: string;
    children: ReactNode;
};

export function Modal({
    isOpen,
    title,
    onClose,
    className,
    children,
}: ModalProps) {
    const titleId = useId();
    const dialogRef = useRef<HTMLDivElement>(null);
    const previouslyFocusedRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        previouslyFocusedRef.current =
            document.activeElement as HTMLElement | null;
        dialogRef.current?.focus();

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
        <div
            // 中身がビューポートより高いときは背景側をスクロールさせる。
            // items-center のままだと上端が切れてスクロールで到達できなくなるため、
            // items-start + 本体の my-auto で「短いときは中央・長いときは上端から」にする
            className="fixed inset-0 z-[75] flex items-start justify-center overflow-y-auto bg-ink/50 p-6"
            onClick={onClose}
        >
            <div
                ref={dialogRef}
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                className={cn(
                    'my-auto w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl outline-none',
                    className,
                )}
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex items-center">
                    <h2 id={titleId} className="text-base font-extrabold">
                        {title}
                    </h2>
                    <button
                        type="button"
                        aria-label="閉じる"
                        className="ml-auto text-xl text-ink2"
                        onClick={onClose}
                    >
                        ×
                    </button>
                </div>
                <div className="mt-4">{children}</div>
            </div>
        </div>
    );
}
