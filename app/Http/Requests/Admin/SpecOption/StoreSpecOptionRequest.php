<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SpecOption;

use App\Enums\SpecOptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecOptionRequest extends FormRequest
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
            'type' => ['required', Rule::enum(SpecOptionType::class)],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('spec_options', 'name')
                    ->where('type', $this->input('type')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => '規格種別',
            'name' => '規格値',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'この規格値はすでに登録されています。',
        ];
    }
}
