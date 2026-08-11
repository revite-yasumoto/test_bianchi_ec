<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Stock;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'stocks' => ['required', 'array', 'min:1', 'max:200'],
            'stocks.*.id' => ['required', 'integer', 'exists:stocks,id'],
            'stocks.*.quantity' => ['required', 'integer', 'min:0', 'max:999999'],

            // 更新対象が本当に表示中の絞り込み結果かをサーバー側で再現するために受け取る
            'has_sku' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stocks' => '在庫',
            'stocks.*.quantity' => '在庫数',
        ];
    }
}
