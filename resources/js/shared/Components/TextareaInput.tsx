import type { TextareaHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';
import {
    FIELD_CONTROL_CLASS,
    FormField,
    type FormFieldProps,
} from '@/shared/Components/FormField';

type TextareaInputProps = Omit<
    TextareaHTMLAttributes<HTMLTextAreaElement>,
    'id'
> &
    FormFieldProps;

export function TextareaInput({
    id,
    label,
    error,
    required,
    className,
    rows = 6,
    ...props
}: TextareaInputProps) {
    return (
        <FormField
            id={id}
            label={label}
            error={error}
            required={required}
            className={className}
        >
            <textarea
                id={id}
                rows={rows}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={cn(FIELD_CONTROL_CLASS, error && 'border-coral')}
                {...props}
            />
        </FormField>
    );
}
