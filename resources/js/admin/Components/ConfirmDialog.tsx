import { useEffect, useId, useRef } from 'react';
import { cn } from '@/lib/utils';

type ConfirmDialogProps = {
    isOpen: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    confirmVariant?: 'default' | 'danger';
    onConfirm: () => void;
    onCancel: () => void;
};

export function ConfirmDialog({
    isOpen,
    title,
    message,
    confirmLabel,
    confirmVariant = 'default',
    onConfirm,
    onCancel,
}: ConfirmDialogProps) {
    const titleId = useId();
    const messageId = useId();
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
                onCancel();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            previouslyFocusedRef.current?.focus();
        };
    }, [isOpen, onCancel]);

    if (!isOpen) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            onClick={onCancel}
        >
            <div
                ref={dialogRef}
                tabIndex={-1}
                className="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl outline-none"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={messageId}
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id={titleId} className="text-base font-bold text-admin-ink">
                    {title}
                </h2>
                <p id={messageId} className="mt-2 text-sm text-admin-ink-muted">
                    {message}
                </p>
                <div className="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        className="rounded-lg border border-admin-line px-4 py-2 text-sm font-bold text-admin-ink"
                        onClick={onCancel}
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        className={cn(
                            'rounded-lg px-4 py-2 text-sm font-bold text-white',
                            confirmVariant === 'danger'
                                ? 'bg-admin-danger'
                                : 'bg-admin-brand',
                        )}
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
