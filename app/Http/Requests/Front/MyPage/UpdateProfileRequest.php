<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\MyPage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // 全角カタカナ・長音符・全角/半角スペースのみ許可する
            'name_kana' => ['nullable', 'string', 'max:100', 'regex:/\A[ァ-ヶー\x{3000}\s]+\z/u'],
            'email' => [
                'required', 'string', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($this->user('web')?->id),
            ],
            'tel' => ['nullable', 'string', 'regex:/\A[\d-]{10,20}\z/'],
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
            'tel' => '電話番号',
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
        ];
    }
}
