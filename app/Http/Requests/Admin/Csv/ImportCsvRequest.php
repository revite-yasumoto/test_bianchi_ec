<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Csv;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvRequest extends FormRequest
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
            // CSV の MIME は出力元によって揺れるため、拡張子と代表的な MIME の双方を許可する
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'CSVファイル',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'CSVファイル（.csv / .txt）を選択してください。',
            'file.mimetypes' => 'CSVファイル（.csv / .txt）を選択してください。',
            'file.max' => 'CSVファイルは10MB以下にしてください。',
        ];
    }
}
