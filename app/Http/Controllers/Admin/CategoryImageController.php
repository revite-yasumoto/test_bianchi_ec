<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\UpdateCategoryImageRequest;
use App\Models\Category;
use App\Services\Admin\Category\CategoryImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class CategoryImageController extends Controller
{
    public function update(UpdateCategoryImageRequest $request, Category $category, CategoryImageService $service): RedirectResponse
    {
        /** @var UploadedFile $image */
        $image = $request->file('image');

        $service->replace($category, $image);

        return back();
    }

    public function destroy(Category $category, CategoryImageService $service): RedirectResponse
    {
        $service->remove($category);

        return back();
    }
}
