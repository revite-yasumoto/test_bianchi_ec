<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Product;

use Illuminate\Validation\Rule;

class StoreProductRequest extends BaseProductRequest
{
    /**
     * @return array<int, mixed>
     */
    protected function productCodeRules(): array
    {
        return [
            'required',
            'string',
            'max:50',
            'regex:/\A[A-Za-z0-9-]+\z/',
            Rule::unique('products', 'product_code'),
        ];
    }
}
