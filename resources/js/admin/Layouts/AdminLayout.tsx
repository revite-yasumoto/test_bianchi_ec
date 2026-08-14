import { Head } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { Sidebar } from '@/admin/Components/Sidebar';
import { PageHeader } from '@/admin/Components/PageHeader';

const TOAST_DURATION_MS = 2200;

type AdminToastContextValue = {
    showToast: (message: string) => void;
};

const AdminToastContext = createContext<AdminToastContextValue | null>(null);

/** AdminLayout配下のページから `showToast()` でトーストを表示するためのHook */
export function useAdminToast(): AdminToastContextValue {
    const context = useContext(AdminToastContext);

    if (!context) {
        throw new Error(
            'useAdminToast は AdminLayout の子コンポーネント内でのみ使用できます',
        );
    }

    return context;
}

function useToast() {
    const [message, setMessage] = useState<string | null>(null);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, []);

    const showToast = useCallback((nextMessage: string) => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        setMessage(nextMessage);
        timeoutRef.current = setTimeout(
            () => setMessage(null),
            TOAST_DURATION_MS,
        );
    }, []);

    return { message, showToast };
}

type AdminLayoutProps = {
    title: string;
    headerActions?: ReactNode;
    children: ReactNode;
};

export function AdminLayout({
    title,
    headerActions,
    children,
}: AdminLayoutProps) {
    const { message, showToast } = useToast();
    const contextValue = useMemo(() => ({ showToast }), [showToast]);

    return (
        <AdminToastContext.Provider value={contextValue}>
            <Head title={title}>
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <div className="flex min-h-dvh bg-admin-bg">
                <Sidebar />
                <div className="flex min-w-0 flex-1 flex-col">
                    <PageHeader title={title} actions={headerActions} />
                    <main className="flex-1 overflow-y-auto p-6">
                        {children}
                    </main>
                </div>
            </div>
            {message ? (
                <div
                    role="status"
                    aria-live="polite"
                    className="fixed bottom-6 left-1/2 -translate-x-1/2 rounded-lg bg-admin-ink px-5 py-3 text-sm font-bold text-white shadow-lg"
                >
                    {message}
                </div>
            ) : null}
        </AdminToastContext.Provider>
    );
}
