<?php

declare(strict_types=1);

namespace App\Services\Admin\Category;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CategoryImageService
{
    public function replace(Category $category, UploadedFile $file): void
    {
        $previousPath = $category->image_path;

        $category->update(['image_path' => $file->store('categories', 'public')]);

        // 更新に失敗したときに表示中の画像を失わないよう、旧ファイルはレコードの更新後に消す
        if ($previousPath !== null) {
            Storage::disk('public')->delete($previousPath);
        }
    }

    /**
     * カテゴリのレコードを削除した後に、残った画像ファイルを消す。
     */
    public function deleteStoredImage(Category $category): void
    {
        if ($category->image_path !== null) {
            Storage::disk('public')->delete($category->image_path);
        }
    }

    public function remove(Category $category): void
    {
        $path = $category->image_path;

        if ($path === null) {
            return;
        }

        $category->update(['image_path' => null]);

        Storage::disk('public')->delete($path);
    }
}
