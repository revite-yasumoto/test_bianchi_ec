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
}
