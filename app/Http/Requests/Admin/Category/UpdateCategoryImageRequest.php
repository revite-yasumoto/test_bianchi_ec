<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Category;

use App\Support\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryImageRequest extends FormRequest
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
            'image' => ['required', ...ImageUploadRules::file()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'image' => '画像',
        ];
    }
}
