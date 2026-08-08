<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if ($request->is('admin/*') || $request->is('admin')) {
            return [
                ...parent::share($request),
                'auth' => [
                    'admin' => $this->adminAuthProps($request),
                ],
            ];
        }

        return [
            ...parent::share($request),
        ];
    }

    /**
     * サイドバーの管理者名・メール表示にのみ使うため、この2カラムのみをallowlistとして共有する。
     *
     * @return array{name: string, email: string}|null
     */
    private function adminAuthProps(Request $request): ?array
    {
        /** @var Admin|null $admin */
        $admin = $request->user('admin');

        if (! $admin) {
            return null;
        }

        return [
            'name' => $admin->name,
            'email' => $admin->email,
        ];
    }
}
