<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\MyPage;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password:web'],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => 'パスワード',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.current_password' => 'パスワードが正しくありません。',
            'agree.accepted' => '退会に関する内容へのご同意が必要です。',
        ];
    }
}
