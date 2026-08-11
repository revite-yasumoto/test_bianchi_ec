<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 商品IDの一意ルールは登録・更新で異なるため、派生クラスが返す。
     *
     * @return array<int, mixed>
     */
    abstract protected function productCodeRules(): array;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_code' => $this->productCodeRules(),
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0', 'max:9999999'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['required', 'boolean'],
            'has_sku' => ['required', 'boolean'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:max_width=4000,max_height=4000',
            ],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => ['integer'],

            'specs' => ['nullable', 'array', 'max:20'],
            'specs.*.label' => ['required', 'string', 'max:100'],
            'specs.*.value' => ['required', 'string', 'max:255'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.is_available' => ['required', 'boolean'],
            'variants.*.size_name' => ['nullable', 'string', 'max:50', 'required_if:has_sku,1,true'],
            'variants.*.color_name' => ['nullable', 'string', 'max:50', 'required_if:has_sku,1,true'],
            'variants.*.branch_code' => ['nullable', 'string', 'max:20', 'regex:/\A[A-Za-z0-9]+\z/'],
            'variants.*.quantity' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /** 更新時に画面へ残っている既存画像の枚数。新規登録では0件。 */
    protected function keptImageCount(): int
    {
        return 0;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateImageCount($validator);
            $this->validateVariants($validator);
        });
    }

    /**
     * 1商品あたりの上限は既存の残り＋新規アップロードの合計で判定する。
     */
    private function validateImageCount(Validator $validator): void
    {
        $total = $this->keptImageCount() + count((array) $this->file('images', []));

        if ($total > 10) {
            $validator->errors()->add('images', '商品画像は最大10枚までです。');
        }
    }

    /**
     * 配列内の相関チェック（取扱ありの行の必須項目・枝番の商品内重複）はルール記法で表せないためここで行う。
     */
    private function validateVariants(Validator $validator): void
    {
        $hasSku = $this->boolean('has_sku');
        /** @var array<int, array<string, mixed>> $variants */
        $variants = (array) $this->input('variants', []);
        $seenBranchCodes = [];

        foreach ($variants as $index => $variant) {
            $isAvailable = filter_var(
                $variant['is_available'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            );

            if (! $isAvailable) {
                continue;
            }

            if ($variant['quantity'] === null || $variant['quantity'] === '') {
                $validator->errors()->add(
                    "variants.{$index}.quantity",
                    '取扱ありのSKUには在庫数を入力してください。',
                );
            }

            if (! $hasSku) {
                continue;
            }

            $branchCode = (string) ($variant['branch_code'] ?? '');

            if ($branchCode === '') {
                $validator->errors()->add(
                    "variants.{$index}.branch_code",
                    '取扱ありのSKUには枝番を入力してください。',
                );

                continue;
            }

            if (in_array($branchCode, $seenBranchCodes, true)) {
                $validator->errors()->add(
                    "variants.{$index}.branch_code",
                    '枝番が商品内で重複しています。',
                );
            }

            $seenBranchCodes[] = $branchCode;
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_code' => '商品ID',
            'name' => '商品名',
            'category_id' => 'カテゴリ',
            'price' => '価格',
            'description' => '商品説明',
            'is_published' => '公開状態',
            'has_sku' => 'SKUの有無',
            'images' => '商品画像',
            'specs' => '商品スペック',
            'variants' => 'SKU',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.max' => '商品画像は最大10枚までです。',
            'product_code.regex' => '商品IDは半角英数とハイフンのみ使用できます。',
            'variants.*.branch_code.regex' => '枝番は半角英数のみ使用できます。',
        ];
    }
}
