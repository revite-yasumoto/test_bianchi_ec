export {};

declare global {
    function route(
        name: string,
        params?: Record<string, unknown> | unknown[],
        absolute?: boolean,
    ): string;
    function route(): {
        current: (name?: string) => boolean;
        has: (name: string) => boolean;
    };

    /** Laravel の `paginate()` が返すページャの形（`through()` 適用後も同じ） */
    type Paginated<T> = {
        data: T[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };

    type AdminAuthUser = {
        name: string;
        email: string;
    };

    /** CSVインポートの結果。エラーがある場合は1件も適用されていない */
    type CsvImportResult = {
        created: number;
        updated: number;
        errors: { line: number; message: string }[];
    };

    type AdminSharedProps = {
        auth: {
            admin: AdminAuthUser | null;
        };
        flash: {
            importResult: CsvImportResult | null;
        };
    };

    type FrontAuthUser = {
        id: number;
        member_code: string;
        name: string;
    };

    /** ヘッダーから開くカートドロワーに出す明細 */
    type CartDrawerItem = {
        id: number;
        name: string;
        variant_label: string;
        quantity: number;
        line_total: number;
        image_url: string | null;
        category_name: string;
    };

    type FrontSharedProps = {
        auth: {
            user: FrontAuthUser | null;
        };
        cartCount: number;
        cartItems: CartDrawerItem[];
        /** 送料無料のしきい値。カートが空のときは案内を出さないため null */
        freeShippingThreshold: number | null;
        favoriteCount: number;
        flash: {
            success: string | null;
        };
    };
}
