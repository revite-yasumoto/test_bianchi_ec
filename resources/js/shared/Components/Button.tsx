import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type ButtonVariant = 'primary' | 'cta' | 'outline' | 'ghost';

const VARIANT_CLASS: Record<ButtonVariant, string> = {
    primary: 'bg-brand text-white hover:bg-brand-deep',
    cta: 'bg-coral text-white hover:bg-coral/90',
    outline:
        'border border-line bg-white text-ink hover:border-brand hover:text-brand',
    ghost: 'text-brand hover:text-coral',
};

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: ButtonVariant;
};

export function Button({
    variant = 'primary',
    type = 'button',
    className,
    children,
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-extrabold transition-colors disabled:cursor-not-allowed disabled:opacity-60',
                VARIANT_CLASS[variant],
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}
