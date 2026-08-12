<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Cart\StoreCartItemRequest;
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
}
