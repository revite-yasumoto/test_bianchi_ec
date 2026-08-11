import { Head } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { CartDrawer } from '@/front/Components/CartDrawer';
import { Footer } from '@/front/Components/Footer';
import { Header } from '@/front/Components/Header';
import { Toast } from '@/shared/Components/Toast';

type FrontLayoutProps = {
    title: string;
    description?: string;
    children: ReactNode;
};

export function FrontLayout({
    title,
    description,
    children,
}: FrontLayoutProps) {
    const [isCartOpen, setIsCartOpen] = useState(false);

    return (
        <>
            <Head title={title}>
                {description ? (
                    <meta name="description" content={description} />
                ) : null}
            </Head>
            <div className="flex min-h-dvh flex-col bg-white">
                <Header onOpenCart={() => setIsCartOpen(true)} />
                <main className="flex-1">{children}</main>
                <Footer />
            </div>
            <CartDrawer
                isOpen={isCartOpen}
                onClose={() => setIsCartOpen(false)}
            />
            <Toast />
        </>
    );
}
