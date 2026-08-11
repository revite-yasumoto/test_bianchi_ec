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

    type AdminSharedProps = {
        auth: {
            admin: AdminAuthUser | null;
        };
    };

    type FrontAuthUser = {
        id: number;
        member_code: string;
        name: string;
    };

    type FrontSharedProps = {
        auth: {
            user: FrontAuthUser | null;
        };
        cartCount: number;
        favoriteCount: number;
        flash: {
            success: string | null;
        };
    };
}
