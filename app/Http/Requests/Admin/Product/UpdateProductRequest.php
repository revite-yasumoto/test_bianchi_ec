<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends BaseProductRequest
{
    protected function keptImageCount(): int
    {
        /** @var Product $product */
        $product = $this->route('product');

        $deletedIds = array_map('intval', (array) $this->input('deleted_image_ids', []));

        return $product->images()->whereNotIn('id', $deletedIds === [] ? [0] : $deletedIds)->count();
    }

    /**
     * @return array<int, mixed>
     */
    protected function productCodeRules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'required',
            'string',
            'max:50',
            'regex:/\A[A-Za-z0-9-]+\z/',
            Rule::unique('products', 'product_code')->ignore($product->id),
        ];
    }
}
