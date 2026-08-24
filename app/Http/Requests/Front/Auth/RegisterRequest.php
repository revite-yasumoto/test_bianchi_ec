<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Auth;

use App\Enums\Gender;
use App\Http\Requests\Concerns\ValidatesBirthdayParts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    use ValidatesBirthdayParts;

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
            'name' => ['required', 'string', 'max:100'],
            // 全角カタカナ・長音符・全角/半角スペースのみ許可する
            'name_kana' => ['nullable', 'string', 'max:100', 'regex:/\A[ァ-ヶー\x{3000}\s]+\z/u'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'tel' => ['required', 'string', 'regex:/\A[\d-]{10,20}\z/'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'is_newsletter_subscribed' => ['nullable', 'boolean'],
            // 住所の規則は配送先の追加（Front\Address\StoreUserAddressRequest）と揃える
            'postal_code' => ['required', 'string', 'max:8', 'regex:/\A\d{3}-?\d{4}\z/'],
            'prefecture_id' => ['required', 'integer', 'exists:prefectures,id'],
            'city' => ['required', 'string', 'max:100'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'agree' => ['accepted'],
            ...$this->birthdayPartRules(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateBirthdayParts($validator),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'name_kana' => 'お名前（カナ）',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'tel' => '電話番号',
            'gender' => '性別',
            'postal_code' => '郵便番号',
            'prefecture_id' => '都道府県',
            'city' => '市区町村',
            'address_line1' => '番地',
            'address_line2' => '建物名・部屋番号',
            'agree' => '利用規約およびプライバシーポリシーへの同意',
            ...$this->birthdayPartAttributes(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name_kana.regex' => 'お名前（カナ）は全角カタカナで入力してください。',
            'tel.regex' => '電話番号は数字とハイフンで入力してください。',
            'postal_code.regex' => '郵便番号は7桁の数字で入力してください。',
        ];
    }
}
