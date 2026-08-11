<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\News;

use App\Enums\NewsCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 作成・更新で入力項目・ルールが同一のため、両方で使う。
 */
class SaveNewsRequest extends FormRequest
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
            'published_on' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::enum(NewsCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'published_on' => '掲載日',
            'category' => '種別',
            'title' => 'タイトル',
            'body' => '本文',
            'is_published' => '公開状態',
        ];
    }
}
