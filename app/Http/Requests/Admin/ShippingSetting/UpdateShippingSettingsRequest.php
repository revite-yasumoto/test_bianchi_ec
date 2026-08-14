<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ShippingSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingSettingsRequest extends FormRequest
{
    /** 都道府県は47件固定で、追加・削除を行わない */
    private const PREFECTURE_COUNT = 47;

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
            'settings' => ['required', 'array', 'size:'.self::PREFECTURE_COUNT],
            'settings.*.id' => ['required', 'integer', 'distinct', 'exists:shipping_settings,id'],
            'settings.*.fee' => ['required', 'integer', 'min:0', 'max:100000'],
            'settings.*.delivery_days' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'settings' => '送料設定',
            'settings.*.fee' => '送料',
            'settings.*.delivery_days' => '配送予定日数',
        ];
    }
}
