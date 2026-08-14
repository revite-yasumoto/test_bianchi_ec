<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Actions\Front\Address\StoreUserAddress;
use App\Actions\Front\Address\UpdateUserAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Address\StoreUserAddressRequest;
use App\Http\Requests\Front\Address\UpdateUserAddressRequest;
use App\Models\User;
use App\Models\UserAddress;
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

    public function update(
        UpdateUserAddressRequest $request,
        UserAddress $address,
        UpdateUserAddress $updateUserAddress,
    ): RedirectResponse {
        $updateUserAddress($address, [
            ...$request->safe()->all(),
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'お届け先を更新しました');
    }

    public function destroy(UserAddress $address): RedirectResponse
    {
        $address->delete();

        return back()->with('success', 'お届け先を削除しました');
    }
}
