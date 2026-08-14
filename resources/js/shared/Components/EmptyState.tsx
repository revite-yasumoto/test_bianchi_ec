import { cn } from '@/lib/utils';

type EmptyStateProps = {
    message: string;
    className?: string;
};

export function EmptyState({ message, className }: EmptyStateProps) {
    return (
        <p className={cn('py-10 text-center text-sm text-ink2', className)}>
            {message}
        </p>
    );
}
