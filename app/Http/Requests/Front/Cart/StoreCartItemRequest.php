<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Cart;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartItemRequest extends FormRequest
{
    /** 1商品（バリエーション）あたりカートに入れられる数量の上限 */
    public const MAX_QUANTITY = 99;

    private ?ProductVariant $variant = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
        ];
    }

    /**
     * 公開状態・取扱可否・在庫は表示時点から変わりうるため、投入時にサーバー側で必ず再検証する。
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

                $variant = $this->variant();

                if (! $variant->is_available || ! $variant->product?->is_published) {
                    $validator->errors()->add('product_variant_id', 'この商品は現在購入できません。');

                    return;
                }

                $quantity = $this->currentQuantity() + $this->integer('quantity');

                if ($quantity > ($variant->stock?->quantity ?? 0)) {
                    $validator->errors()->add('quantity', '在庫が不足しています。');

                    return;
                }

                if ($quantity > self::MAX_QUANTITY) {
                    $validator->errors()->add(
                        'quantity',
                        'カートに入れられる数量は1商品につき'.self::MAX_QUANTITY.'個までです。',
                    );
                }
            },
        ];
    }

    public function variant(): ProductVariant
    {
        return $this->variant ??= ProductVariant::query()
            ->with(['product', 'stock'])
            ->findOrFail($this->integer('product_variant_id'));
    }

    /**
     * すでにカートへ入っている数量。追加分と合算して在庫・上限を判定する。
     */
    private function currentQuantity(): int
    {
        /** @var User $user */
        $user = $this->user('web');

        return (int) $user->cartItems()
            ->where('product_variant_id', $this->integer('product_variant_id'))
            ->value('quantity');
    }
}
