import type { ReactNode } from 'react';
import { LogoutButton } from './LogoutButton';

type PageHeaderProps = {
    title: string;
    actions?: ReactNode;
};

export function PageHeader({ title, actions }: PageHeaderProps) {
    return (
        <header className="flex items-center gap-4 border-b border-admin-line bg-white px-6 py-4">
            <h1 className="text-lg font-extrabold text-admin-ink">{title}</h1>
            <div className="ml-auto flex items-center gap-2">
                {actions}
                <LogoutButton />
            </div>
        </header>
    );
}
