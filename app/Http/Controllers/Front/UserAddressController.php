<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Actions\Front\Address\StoreUserAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Address\StoreUserAddressRequest;
use App\Models\User;
use App\Services\Front\Checkout\CheckoutService;
use Illuminate\Http\RedirectResponse;

class UserAddressController extends Controller
{
    public function store(StoreUserAddressRequest $request, StoreUserAddress $storeUserAddress): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $address = $storeUserAddress($user, [
            ...$request->safe()->except(['use_for_checkout']),
            'is_default' => $request->boolean('is_default'),
        ]);

        // 購入手続きのモーダルから追加した場合は、追加した住所をそのまま配送先として選択済みにする
        if ($request->boolean('use_for_checkout')) {
            $request->session()->put(CheckoutService::SESSION_ADDRESS_ID, $address->id);
        }

        return back()->with('success', 'お届け先を追加しました');
    }
}
