import type { ReactNode } from 'react';

export type FormFieldProps = {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    className?: string;
};

/** TextInput / SelectInput / TextareaInput が共通で使うラベルとエラー表示の枠 */
export function FormField({
    id,
    label,
    error,
    required,
    className,
    children,
}: FormFieldProps & { children: ReactNode }) {
    return (
        <div className={className}>
            <label
                htmlFor={id}
                className="mb-1.5 block text-xs font-bold text-ink"
            >
                {label}
                {required ? (
                    <span className="ml-1 text-coral" aria-hidden="true">
                        *
                    </span>
                ) : null}
            </label>
            {children}
            {error ? (
                <p
                    id={`${id}-error`}
                    className="mt-1.5 text-[11.5px] font-bold text-coral"
                >
                    {error}
                </p>
            ) : null}
        </div>
    );
}

/** 入力要素に共通で当てる枠線・余白 */
export const FIELD_CONTROL_CLASS =
    'w-full rounded-xl border border-line bg-white px-3.5 py-3 text-base text-ink placeholder:text-ink2/60 focus:border-brand focus:outline-none';
