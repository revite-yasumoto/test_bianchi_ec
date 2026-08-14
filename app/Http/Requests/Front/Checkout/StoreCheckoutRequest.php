<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Checkout;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // 他人の住所IDを送られても通らないよう、存在確認をログイン中の会員の住所に限定する
            'address_id' => [
                'required',
                'integer',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user('web')?->id),
            ],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'address_id' => 'お届け先',
            'payment_method' => 'お支払い方法',
        ];
    }
}
