import { Head } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { CartDrawer } from '@/front/Components/CartDrawer';
import { Footer } from '@/front/Components/Footer';
import { Header } from '@/front/Components/Header';
import { Toast } from '@/shared/Components/Toast';

type CartDrawerContextValue = {
    openCart: () => void;
};

const CartDrawerContext = createContext<CartDrawerContextValue | null>(null);

/** FrontLayout配下のページからカートドロワーを開くためのHook（カート投入直後に使う） */
export function useCartDrawer(): CartDrawerContextValue {
    const context = useContext(CartDrawerContext);

    if (!context) {
        throw new Error(
            'useCartDrawer は FrontLayout の子コンポーネント内でのみ使用できます',
        );
    }

    return context;
}

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
    const openCart = useCallback(() => setIsCartOpen(true), []);
    // CartDrawer のフォーカス退避・復元が親の再レンダーでやり直されないよう、参照を固定する
    const closeCart = useCallback(() => setIsCartOpen(false), []);
    const contextValue = useMemo(() => ({ openCart }), [openCart]);

    return (
        <CartDrawerContext.Provider value={contextValue}>
            <Head title={title}>
                {description ? (
                    <meta name="description" content={description} />
                ) : null}
            </Head>
            <div className="flex min-h-dvh flex-col bg-white">
                <Header onOpenCart={openCart} />
                <main className="flex-1">{children}</main>
                <Footer />
            </div>
            <CartDrawer isOpen={isCartOpen} onClose={closeCart} />
            <Toast />
        </CartDrawerContext.Provider>
    );
}
