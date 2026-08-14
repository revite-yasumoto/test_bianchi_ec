<?php

declare(strict_types=1);

namespace App\Actions\Admin\Product;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SyncProductImages
{
    /** `(product_id, sort_order)` の一意制約に触れずに並びを組み替えるための退避値 */
    private const TEMP_OFFSET = 100;

    /**
     * 既存の残り（表示順）→ 新規アップロードの順に並べ、`sort_order` を 0 から振り直す。
     *
     * @param  array<int, UploadedFile>  $uploadedFiles
     * @param  array<int, int>  $deletedImageIds
     */
    public function __invoke(Product $product, array $uploadedFiles, array $deletedImageIds): void
    {
        $this->deleteImages($product, $deletedImageIds);

        $remaining = $product->images()->orderBy('sort_order')->get();

        foreach ($remaining as $offset => $image) {
            $image->update(['sort_order' => $offset + self::TEMP_OFFSET]);
        }

        $nextOrder = $remaining->count() + self::TEMP_OFFSET;

        foreach ($uploadedFiles as $file) {
            $product->images()->create([
                'path' => $file->store('products/'.$product->id, 'public'),
                'sort_order' => $nextOrder,
            ]);

            $nextOrder++;
        }

        foreach ($product->images()->orderBy('sort_order')->get() as $index => $image) {
            $image->update(['sort_order' => $index]);
        }
    }

    /**
     * 商品ごと削除するときに実ファイルを残さないため、レコード削除の前にストレージから消す。
     */
    public function deleteAll(Product $product): void
    {
        foreach ($product->images()->get() as $image) {
            Storage::disk('public')->delete($image->path);
        }
    }

    /**
     * @param  array<int, int>  $deletedImageIds
     */
    private function deleteImages(Product $product, array $deletedImageIds): void
    {
        if ($deletedImageIds === []) {
            return;
        }

        foreach ($product->images()->whereIn('id', $deletedImageIds)->get() as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }
}
