<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => $category->products_count,
            ]);

        return Inertia::render('admin/Category/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create([
            'name' => $request->string('name')->toString(),
            'sort_order' => (int) Category::query()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // products.category_id は ON DELETE RESTRICT のため、DBエラーになる前にアプリ側で弾く
        if ($category->products()->exists()) {
            return back()->withErrors([
                'delete' => 'このカテゴリには商品が登録されているため削除できません。',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index');
    }
}
