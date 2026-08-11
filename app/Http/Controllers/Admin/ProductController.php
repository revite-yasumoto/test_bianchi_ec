<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Product\BuildProductFilter;
use App\Enums\SpecOptionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\SpecOption;
use App\Services\Admin\Product\ProductSaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const PER_PAGE = 50;

    /** SKU有無の絞り込みに許可する値 */
    private const HAS_SKU_OPTIONS = ['all', 'with', 'without'];

    public function __construct(private readonly ProductSaveService $saveService) {}

    public function index(Request $request, BuildProductFilter $buildFilter): Response
    {
        $filters = $this->filtersOf($request);

        $products = $buildFilter($filters)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'product_code' => $product->product_code,
                'name' => $product->name,
                'category_name' => $product->category->name,
                'price' => $product->price,
                'total_stock' => (int) ($product->stocks_sum_quantity ?? 0),
                'has_sku' => $product->has_sku,
                'is_published' => $product->is_published,
            ]);

        return Inertia::render('admin/Product/Index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'filters' => $filters,
            'totalCount' => Product::query()->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Product/Form', [
            'product' => null,
            'categories' => $this->categoryOptions(),
            'sizeOptions' => $this->specOptionNames(SpecOptionType::Size),
            'colorOptions' => $this->specOptionNames(SpecOptionType::Color),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->saveService->save(null, $request->validated());

        return redirect()->route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'specs', 'variants.stock']);

        return Inertia::render('admin/Product/Form', [
            'product' => $this->formData($product),
            'categories' => $this->categoryOptions(),
            'sizeOptions' => $this->specOptionNames(SpecOptionType::Size),
            'colorOptions' => $this->specOptionNames(SpecOptionType::Color),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->saveService->save($product, $request->validated());

        return redirect()->route('admin.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->saveService->delete($product);

        return redirect()->route('admin.products.index');
    }

    /**
     * @return array{q: string|null, category_id: int|null, has_sku: string, price_min: int|null, price_max: int|null}
     */
    private function filtersOf(Request $request): array
    {
        $hasSku = (string) $request->input('has_sku', 'all');

        return [
            'q' => $request->filled('q') ? $request->string('q')->toString() : null,
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : null,
            'has_sku' => in_array($hasSku, self::HAS_SKU_OPTIONS, true) ? $hasSku : 'all',
            'price_min' => $request->filled('price_min') ? $request->integer('price_min') : null,
            'price_max' => $request->filled('price_max') ? $request->integer('price_max') : null,
        ];
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
     * @return array<int, string>
     */
    private function specOptionNames(SpecOptionType $type): array
    {
        return SpecOption::query()
            ->where('type', $type->value)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Product $product): array
    {
        return [
            'id' => $product->id,
            'product_code' => $product->product_code,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => $product->price,
            'description' => $product->description,
            'is_published' => $product->is_published,
            'has_sku' => $product->has_sku,
            'images' => $product->images
                ->map(fn (ProductImage $image): array => [
                    'id' => $image->id,
                    'url' => Storage::disk('public')->url($image->path),
                ])
                ->all(),
            'specs' => $product->specs
                ->map(fn (ProductSpec $spec): array => [
                    'label' => $spec->label,
                    'value' => $spec->value,
                ])
                ->all(),
            'variants' => $product->variants
                ->map(fn (ProductVariant $variant): array => [
                    'size_name' => $variant->size_name,
                    'color_name' => $variant->color_name,
                    'branch_code' => $variant->branch_code,
                    'is_available' => $variant->is_available,
                    'quantity' => $variant->stock?->quantity ?? 0,
                ])
                ->all(),
        ];
    }
}
