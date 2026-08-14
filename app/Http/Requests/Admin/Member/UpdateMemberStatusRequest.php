<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Member;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(UserStatus::class)->only(UserStatus::administrable())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var User $user */
            $user = $this->route('user');

            if ($user->status === UserStatus::Withdrawn) {
                $validator->errors()->add('status', '退会済みの会員のステータスは変更できません。');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'ステータス',
        ];
    }
}
