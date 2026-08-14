<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Actions\Front\Product\BuildProductCard;
use App\Actions\Front\Product\BuildProductDetail;
use App\Actions\Front\Product\RecordBrowsingHistory;
use App\Enums\PriceRange;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\Setting\EcSettingProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const PER_PAGE = 24;

    public function index(Request $request, BuildProductCard $buildCard): Response
    {
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;

        // 任意の値・配列が渡りうるため、区分に無い値は絞り込みに使わない
        $rawPriceRange = $request->query('price_range');
        $priceRange = is_string($rawPriceRange) ? PriceRange::tryFrom($rawPriceRange) : null;

        $products = $buildCard
            ->scope(Product::query()->where('is_published', true))
            ->when($categoryId, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($priceRange, fn (Builder $query, PriceRange $range) => $this->applyPriceRange($query, $range))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through($buildCard);

        return Inertia::render('front/Product/Index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'priceRanges' => PriceRange::options(),
            'filters' => [
                'category_id' => $categoryId,
                'price_range' => $priceRange?->value,
            ],
            // 絞り込みが無ければページャの総件数と一致するため、追加のCOUNTを発行しない
            'totalCount' => $categoryId === null && $priceRange === null
                ? $products->total()
                : Product::query()->where('is_published', true)->count(),
        ]);
    }

    /**
     * 下限は含み、上限は含まない。上限のない区分は下限のみで絞り込む。
     */
    private function applyPriceRange(Builder $query, PriceRange $range): Builder
    {
        [$min, $max] = $range->bounds();

        return $query->where('price', '>=', $min)
            ->when($max !== null, fn (Builder $inner) => $inner->where('price', '<', $max));
    }

    public function show(
        Request $request,
        Product $product,
        BuildProductDetail $buildDetail,
        RecordBrowsingHistory $recordHistory,
        EcSettingProvider $ecSettingProvider,
    ): Response {
        abort_unless($product->is_published, 404);

        $product->load(['category', 'images', 'specs', 'variants.stock']);

        /** @var User|null $user */
        $user = $request->user('web');

        if ($user) {
            $recordHistory($user, $product);
        }

        $ecSetting = $ecSettingProvider->get();

        return Inertia::render('front/Product/Show', [
            'product' => $buildDetail($product),
            'isFavorited' => $user
                ? $user->favorites()->where('product_id', $product->id)->exists()
                : false,
            'shippingTable' => $this->shippingTable(),
            'ecSetting' => [
                'free_shipping_threshold' => $ecSetting->free_shipping_threshold,
                'cod_fee' => $ecSetting->cod_fee,
            ],
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    /**
     * 「送料・お支払い方法・発送日数について」モーダルに出す都道府県別の一覧。
     *
     * @return array<int, array{prefecture_name: string, fee: int, delivery_days: int}>
     */
    private function shippingTable(): array
    {
        return ShippingSetting::query()
            ->with('prefecture')
            ->orderBy('prefecture_id')
            ->get()
            ->map(fn (ShippingSetting $setting): array => [
                'prefecture_name' => $setting->prefecture->name,
                'fee' => $setting->fee,
                'delivery_days' => $setting->delivery_days,
            ])
            ->all();
    }
}
