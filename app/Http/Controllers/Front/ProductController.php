<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Actions\Front\Product\BuildProductDetail;
use App\Actions\Front\Product\RecordBrowsingHistory;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\Setting\EcSettingProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const PER_PAGE = 24;

    public function index(Request $request): Response
    {
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;

        $products = Product::query()
            ->where('is_published', true)
            ->with(['category', 'mainImage'])
            ->withCount(['variants as in_stock_variants_count' => fn (Builder $query) => $query
                ->where('is_available', true)
                ->whereHas('stock', fn (Builder $stock) => $stock->where('quantity', '>', 0)),
            ])
            ->when($categoryId, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'category_name' => $product->category->name,
                'product_code' => $product->product_code,
                'price' => $product->price,
                'main_image_url' => $this->imageUrl($product->mainImage),
                'is_sold_out' => ((int) $product->in_stock_variants_count) === 0,
            ]);

        return Inertia::render('front/Product/Index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'filters' => ['category_id' => $categoryId],
            // 絞り込みが無ければページャの総件数と一致するため、追加のCOUNTを発行しない
            'totalCount' => $categoryId === null
                ? $products->total()
                : Product::query()->where('is_published', true)->count(),
        ]);
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

    private function imageUrl(?ProductImage $image): ?string
    {
        return $image ? Storage::disk('public')->url($image->path) : null;
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
