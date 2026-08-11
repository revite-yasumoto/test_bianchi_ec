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
