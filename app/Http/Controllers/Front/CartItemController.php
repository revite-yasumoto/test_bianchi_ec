<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Cart\StoreCartItemRequest;
use App\Http\Requests\Front\Cart\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CartItemController extends Controller
{
    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        /** @var CartItem $cartItem */
        $cartItem = $user->cartItems()->firstOrNew([
            'product_variant_id' => $request->integer('product_variant_id'),
        ]);

        $cartItem->quantity = $cartItem->quantity + $request->integer('quantity');
        $cartItem->save();

        return back()->with('success', 'カートに追加しました');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        $cartItem->update(['quantity' => $request->integer('quantity')]);

        return back()->with('success', '数量を変更しました');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $cartItem->delete();

        return back()->with('success', '商品を削除しました');
    }
}
