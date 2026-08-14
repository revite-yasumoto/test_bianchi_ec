<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Cart;

use App\Models\CartItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.StoreCartItemRequest::MAX_QUANTITY],
        ];
    }

    /**
     * 在庫はカートの表示時点から変わりうるため、変更時にサーバー側で必ず再検証する。
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var CartItem $cartItem */
                $cartItem = $this->route('cartItem');
                $cartItem->load('variant.stock');

                if ($this->integer('quantity') > ($cartItem->variant->stock?->quantity ?? 0)) {
                    $validator->errors()->add('quantity', '在庫が不足しています。');
                }
            },
        ];
    }
}
