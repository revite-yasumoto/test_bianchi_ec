<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\EcSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEcSettingRequest extends FormRequest
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
            'free_shipping_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'cod_fee' => ['required', 'integer', 'min:0', 'max:10000'],
            'bank_transfer_note' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'free_shipping_threshold' => '送料無料となる購入金額',
            'cod_fee' => '代引き手数料',
            'bank_transfer_note' => '銀行振込の案内文',
        ];
    }
}
