<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Notice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 作成・更新で入力項目・ルールが同一のため、両方で使う。
 */
class SaveNoticeRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'display_start_on' => ['required', 'date_format:Y-m-d'],
            'display_end_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:display_start_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'body' => '本文',
            'display_start_on' => '掲載開始日',
            'display_end_on' => '掲載終了日',
        ];
    }
}
