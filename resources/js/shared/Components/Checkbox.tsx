import type { InputHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    children: ReactNode;
    error?: string;
};

/**
 * モックの角丸カスタム表示に合わせるため、ネイティブのチェックボックスは視覚的に隠し
 * 隣接する span で見た目を描画する（フォーカス・キーボード操作はネイティブのまま）。
 */
export function Checkbox({
    id,
    children,
    error,
    className,
    ...props
}: CheckboxProps) {
    return (
        <div className={className}>
            <label htmlFor={id} className="flex items-start gap-3">
                <input
                    id={id}
                    type="checkbox"
                    className="peer sr-only"
                    {...props}
                />
                <span
                    aria-hidden="true"
                    className={cn(
                        'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-[5px] border-[1.5px] border-line bg-white text-xs font-bold text-transparent',
                        'peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white',
                        'peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand',
                        error && 'border-coral',
                    )}
                >
                    ✓
                </span>
                <span className="text-xs leading-relaxed text-ink2">
                    {children}
                </span>
            </label>
            {error ? (
                <p className="mt-1.5 text-[11.5px] font-bold text-coral">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
