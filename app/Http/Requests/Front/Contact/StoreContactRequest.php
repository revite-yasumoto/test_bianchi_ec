<?php

declare(strict_types=1);

namespace App\Http\Requests\Front\Contact;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `product_id` に `exists` を課さないのは、存在しない・非公開の商品IDを検証エラーにせず
     * 「対象商品なし」として受け付けるため（リンクを踏んだ利用者から問い合わせ手段を奪わない）。
     * 整数として解釈できない値は `integer` で弾く（この場合のみ送信自体がエラーになる）。
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191'],
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'product_id' => '対象商品',
            'product_name' => '対象商品',
            'body' => 'お問い合わせ内容',
        ];
    }
}
