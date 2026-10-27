<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BannerBackground;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\SaveBannerRequest;
use App\Models\Banner;
use App\Models\Product;
use App\Services\Admin\Banner\BannerSaveService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        $banners = Banner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => $banner->id,
                'tag' => $banner->tag,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'background' => $banner->background->value,
                'background_gradient' => $banner->background->gradient(),
                'product_id' => $banner->product_id,
                'link_url' => $banner->link_url,
                'sort_order' => $banner->sort_order,
                'is_active' => $banner->is_active,
                'state_label' => $banner->is_active ? '公開' : '非公開',
                'pc_image_url' => $banner->pc_image_path !== null ? asset('storage/'.$banner->pc_image_path) : null,
                'sp_image_url' => $banner->sp_image_path !== null ? asset('storage/'.$banner->sp_image_path) : null,
            ]);

        $productOptions = Product::query()
            ->orderBy('product_code')
            ->get(['id', 'product_code', 'name'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'label' => $product->product_code.' '.$product->name,
            ]);

        return Inertia::render('admin/Banner/Index', [
            'banners' => $banners,
            'backgroundOptions' => array_map(
                fn (BannerBackground $background): array => ['value' => $background->value, 'label' => $background->label()],
                BannerBackground::cases(),
            ),
            'productOptions' => $productOptions,
        ]);
    }

    public function store(SaveBannerRequest $request, BannerSaveService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back();
    }

    public function update(SaveBannerRequest $request, Banner $banner, BannerSaveService $service): RedirectResponse
    {
        $service->update($banner, $request->validated());

        return back();
    }

    public function destroy(Banner $banner, BannerSaveService $service): RedirectResponse
    {
        $service->delete($banner);

        return back();
    }
}
