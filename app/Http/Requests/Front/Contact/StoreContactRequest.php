<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Contact;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'product_name' => '対象商品',
            'body' => 'お問い合わせ内容',
        ];
    }
}
