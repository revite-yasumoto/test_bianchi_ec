import type { SelectHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';
import {
    FIELD_CONTROL_CLASS,
    FormField,
    type FormFieldProps,
} from '@/shared/Components/FormField';

type SelectInputProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'id'> &
    FormFieldProps;

export function SelectInput({
    id,
    label,
    error,
    required,
    className,
    children,
    ...props
}: SelectInputProps) {
    return (
        <FormField
            id={id}
            label={label}
            error={error}
            required={required}
            className={className}
        >
            <select
                id={id}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={cn(FIELD_CONTROL_CLASS, error && 'border-coral')}
                {...props}
            >
                {children}
            </select>
        </FormField>
    );
}
