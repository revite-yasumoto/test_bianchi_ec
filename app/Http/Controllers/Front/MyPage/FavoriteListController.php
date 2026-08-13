<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Actions\Front\Product\BuildProductCard;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteListController extends Controller
{
    public function index(Request $request, BuildProductCard $buildProductCard): Response
    {
        /** @var User $user */
        $user = $request->user('web');

        // scope() が積む在庫集計のサブクエリを消さないよう、ここで select() を呼び直さない
        // （`products.*` は scope() 内の withCount() が既に選択している）
        $products = $buildProductCard
            ->scope(Product::query())
            ->join('favorites', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', $user->id)
            // 非公開・削除された商品は一覧に出さない
            ->where('products.is_published', true)
            ->orderByDesc('favorites.id')
            ->get();

        return Inertia::render('front/MyPage/Favorites', [
            'products' => $products->map($buildProductCard)->all(),
        ]);
    }
}
