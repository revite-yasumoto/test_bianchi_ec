<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Favorite\StoreFavoriteRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(StoreFavoriteRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $user->favorites()->firstOrCreate([
            'product_id' => $request->integer('product_id'),
        ]);

        return back()->with('success', 'お気に入りに追加しました');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $user->favorites()->where('product_id', $product->id)->delete();

        return back()->with('success', 'お気に入りを解除しました');
    }
}
