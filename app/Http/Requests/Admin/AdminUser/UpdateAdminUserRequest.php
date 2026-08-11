<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AdminUser;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminUserRequest extends FormRequest
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
        /** @var Admin $admin */
        $admin = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:191',
                Rule::unique('admins', 'email')->ignore($admin->id),
            ],
            // 未入力なら既存のパスワードを保持するため任意にする
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }
}
