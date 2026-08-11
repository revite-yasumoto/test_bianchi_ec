<?php

declare(strict_types=1);

namespace App\Services\Admin\Product;

use App\Actions\Admin\Product\SyncProductImages;
use App\Actions\Admin\Product\SyncProductVariants;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProductSaveService
{
    public function __construct(
        private readonly SyncProductVariants $syncVariants,
        private readonly SyncProductImages $syncImages,
    ) {}

    /**
     * 商品・画像・スペック・バリエーション・在庫を1トランザクションで保存する。
     *
     * @param  array<string, mixed>  $data
     */
    public function save(?Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $attributes = [
                'product_code' => $data['product_code'],
                'name' => $data['name'],
                'category_id' => (int) $data['category_id'],
                'price' => (int) $data['price'],
                'description' => $data['description'] ?? null,
                'has_sku' => filter_var($data['has_sku'], FILTER_VALIDATE_BOOLEAN),
                'is_published' => filter_var($data['is_published'], FILTER_VALIDATE_BOOLEAN),
            ];

            if ($product === null) {
                $product = Product::query()->create($attributes);
            } else {
                $product->update($attributes);
                $product->refresh();
            }

            /** @var array<int, UploadedFile> $images */
            $images = $data['images'] ?? [];
            /** @var array<int, int> $deletedImageIds */
            $deletedImageIds = $data['deleted_image_ids'] ?? [];
            ($this->syncImages)($product, $images, $deletedImageIds);

            $this->saveSpecs($product, $data['specs'] ?? []);

            ($this->syncVariants)($product, $data['variants'] ?? []);

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $this->syncImages->deleteAll($product);

            $product->delete();
        });
    }

    /**
     * スペックは並び順のみの単純な子データのため、差分を取らず作り直す。
     *
     * @param  array<int, array<string, mixed>>  $specs
     */
    private function saveSpecs(Product $product, array $specs): void
    {
        $product->specs()->delete();

        foreach (array_values($specs) as $index => $spec) {
            $product->specs()->create([
                'label' => $spec['label'],
                'value' => $spec['value'],
                'sort_order' => $index,
            ]);
        }
    }
}
