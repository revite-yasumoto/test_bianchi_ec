<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAddressRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:8', 'regex:/\A\d{3}-?\d{4}\z/'],
            'prefecture_id' => ['required', 'integer', 'exists:prefectures,id'],
            'city' => ['required', 'string', 'max:100'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'tel' => ['required', 'string', 'regex:/\A[\d-]{10,20}\z/'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'label' => '表示名',
            'recipient_name' => '宛名',
            'postal_code' => '郵便番号',
            'prefecture_id' => '都道府県',
            'city' => '市区町村',
            'address_line1' => '番地',
            'address_line2' => '建物名・部屋番号',
            'tel' => '電話番号',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => '郵便番号は7桁の数字で入力してください。',
            'tel.regex' => '電話番号は数字とハイフンで入力してください。',
        ];
    }
}
