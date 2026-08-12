<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
     * カート明細は件数表示とドロワーの双方が参照するため、リクエスト内で1度だけ問い合わせる。
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $cartItems = null;

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
                'flash' => [
                    'importResult' => $request->session()->get('importResult'),
                ],
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->userAuthProps($request),
            ],
            'cartCount' => fn (): int => $this->cartCount($request),
            'cartItems' => fn (): array => $this->cartItems($request),
            'favoriteCount' => fn (): int => $this->favoriteCount($request),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }

    /**
     * ヘッダーの会員名表示とマイページ導線にのみ使うため、この3カラムのみをallowlistとして共有する。
     *
     * @return array{id: int, member_code: string, name: string}|null
     */
    private function userAuthProps(Request $request): ?array
    {
        /** @var User|null $user */
        $user = $request->user('web');

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'member_code' => $user->member_code,
            'name' => $user->name,
        ];
    }

    /**
     * ヘッダーのカートボタンに出す点数。モックに合わせて明細数ではなく数量の合計を返す。
     */
    private function cartCount(Request $request): int
    {
        return array_sum(array_column($this->cartItems($request), 'quantity'));
    }

    /**
     * ヘッダーから開くカートドロワーの明細。表示に使う項目のみをallowlistとして共有する。
     *
     * @return array<int, array{id: int, name: string, variant_label: string, quantity: int, line_total: int, image_url: string|null, category_name: string}>
     */
    private function cartItems(Request $request): array
    {
        if ($this->cartItems !== null) {
            return $this->cartItems;
        }

        /** @var User|null $user */
        $user = $request->user('web');

        if (! $user) {
            return $this->cartItems = [];
        }

        return $this->cartItems = $user->cartItems()
            ->with(['variant.product.category', 'variant.product.mainImage'])
            ->orderBy('id')
            ->get()
            ->map(function (CartItem $item): array {
                $product = $item->variant->product;

                return [
                    'id' => $item->id,
                    'name' => $product->name,
                    'variant_label' => $item->variant->displayName(),
                    'quantity' => $item->quantity,
                    'line_total' => $product->price * $item->quantity,
                    'image_url' => $product->mainImage
                        ? Storage::disk('public')->url($product->mainImage->path)
                        : null,
                    'category_name' => $product->category->name,
                ];
            })
            ->all();
    }

    private function favoriteCount(Request $request): int
    {
        /** @var User|null $user */
        $user = $request->user('web');

        return $user ? $user->favorites()->count() : 0;
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
