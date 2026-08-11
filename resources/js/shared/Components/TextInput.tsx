import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';
import {
    FIELD_CONTROL_CLASS,
    FormField,
    type FormFieldProps,
} from '@/shared/Components/FormField';

type TextInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'id'> &
    FormFieldProps;

export function TextInput({
    id,
    label,
    error,
    required,
    className,
    ...props
}: TextInputProps) {
    return (
        <FormField
            id={id}
            label={label}
            error={error}
            required={required}
            className={className}
        >
            <input
                id={id}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={cn(FIELD_CONTROL_CLASS, error && 'border-coral')}
                {...props}
            />
        </FormField>
    );
}
