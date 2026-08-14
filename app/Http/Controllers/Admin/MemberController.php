<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    private const PER_PAGE = 50;

    /** 会員詳細に表示する直近注文の件数 */
    private const RECENT_ORDER_LIMIT = 10;

    public function index(Request $request): Response
    {
        $keyword = $request->filled('q') ? $request->string('q')->toString() : null;

        $members = User::query()
            ->when($keyword, fn (Builder $query, string $value) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('name', 'like', '%'.$this->escapeLike($value).'%')
                    ->orWhere('email', 'like', '%'.$this->escapeLike($value).'%')
                    ->orWhere('member_code', 'like', '%'.$this->escapeLike($value).'%'),
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'member_code' => $user->member_code,
                'name' => $user->name,
                'email' => $user->email,
                'registered_on' => $user->created_at->format('Y.m.d'),
                'status' => $user->status->value,
                'status_label' => $user->status->label(),
            ]);

        return Inertia::render('admin/Member/Index', [
            'members' => $members,
            'filters' => ['q' => $keyword],
            'totalCount' => User::query()->count(),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('addresses.prefecture');

        return Inertia::render('admin/Member/Show', [
            'member' => [
                'id' => $user->id,
                'member_code' => $user->member_code,
                'name' => $user->name,
                'name_kana' => $user->name_kana,
                'email' => $user->email,
                'tel' => $user->tel,
                'registered_on' => $user->created_at->format('Y.m.d'),
                'status' => $user->status->value,
                'status_label' => $user->status->label(),
            ],
            'addresses' => $user->addresses
                ->map(fn (UserAddress $address): array => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'postal_code' => $address->postal_code,
                    'prefecture_name' => $address->prefecture->name,
                    'city' => $address->city,
                    'address_line1' => $address->address_line1,
                    'address_line2' => $address->address_line2,
                    'tel' => $address->tel,
                    'is_default' => $address->is_default,
                ])
                ->all(),
            'recentOrders' => $this->recentOrdersOf($user),
        ]);
    }

    /**
     * @return array<int, array{id: int, order_number: string, ordered_at: string, total: int, status_label: string}>
     */
    private function recentOrdersOf(User $user): array
    {
        return $user->orders()
            ->orderByDesc('ordered_at')
            ->limit(self::RECENT_ORDER_LIMIT)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'ordered_at' => $order->ordered_at->format('Y.m.d'),
                'total' => $order->total,
                'status_label' => $order->status->label(),
            ])
            ->all();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
