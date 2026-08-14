<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCsvImporter
{
    public const HEADER = [
        '商品ID', '商品名', 'カテゴリ', '価格（税込）', 'SKU有無', '枝番', '在庫数', '公開状態',
    ];

    private const SKU_YES = 'あり';

    private const PUBLISHED = '公開';

    /**
     * @param  array<int, array<int, string>>  $rows  行番号 => 列
     */
    public function import(array $rows): ImportResult
    {
        $categoryIds = Category::query()->pluck('id', 'name');
        $errors = [];
        $parsed = [];

        foreach ($rows as $line => $columns) {
            $row = $this->toAssoc($columns);
            $rowErrors = $this->validateRow($row, $categoryIds->keys()->all());

            if ($rowErrors !== []) {
                foreach ($rowErrors as $message) {
                    $errors[] = ['line' => $line, 'message' => $message];
                }

                continue;
            }

            $parsed[] = $row;
        }

        if ($errors !== []) {
            return ImportResult::failed($errors);
        }

        return $this->persist($parsed, $categoryIds->all());
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, string>
     */
    private function toAssoc(array $columns): array
    {
        return [
            'product_code' => $columns[0] ?? '',
            'name' => $columns[1] ?? '',
            'category_name' => $columns[2] ?? '',
            'price' => $columns[3] ?? '',
            'has_sku' => $columns[4] ?? '',
            'branch_code' => $columns[5] ?? '',
            'quantity' => $columns[6] ?? '',
            'is_published' => $columns[7] ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int, string>  $categoryNames
     * @return array<int, string>
     */
    private function validateRow(array $row, array $categoryNames): array
    {
        $validator = Validator::make($row, [
            'product_code' => ['required', 'string', 'max:50', 'regex:/\A[A-Za-z0-9-]+\z/'],
            'name' => ['required', 'string', 'max:255'],
            'category_name' => ['required', 'string', 'in:'.implode(',', $categoryNames)],
            'price' => ['required', 'integer', 'min:0', 'max:9999999'],
            'has_sku' => ['required', 'in:あり,なし'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_published' => ['nullable', 'in:公開,非公開'],
        ], [], [
            'product_code' => '商品ID',
            'name' => '商品名',
            'category_name' => 'カテゴリ',
            'price' => '価格（税込）',
            'has_sku' => 'SKU有無',
            'quantity' => '在庫数',
            'is_published' => '公開状態',
        ]);

        $validator->after(function ($validator) use ($row): void {
            if ($row['has_sku'] === self::SKU_YES && $row['branch_code'] === '') {
                $validator->errors()->add('branch_code', 'SKU有無が「あり」の行には枝番が必要です。');
            }
        });

        return $validator->errors()->all();
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, int>  $categoryIds
     */
    private function persist(array $rows, array $categoryIds): ImportResult
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $categoryIds, &$created, &$updated): void {
            // SKUあり商品は同じ商品IDで複数行が並ぶため、商品単位にまとめてから書き込む
            foreach ($this->groupByProductCode($rows) as $productCode => $productRows) {
                $first = $productRows[0];
                $existing = Product::query()->where('product_code', $productCode)->first();

                $product = Product::query()->updateOrCreate(
                    ['product_code' => $productCode],
                    [
                        'name' => $first['name'],
                        'category_id' => $categoryIds[$first['category_name']],
                        'price' => (int) $first['price'],
                        'has_sku' => $first['has_sku'] === self::SKU_YES,
                        'is_published' => $first['is_published'] === self::PUBLISHED,
                    ],
                );

                $existing === null ? $created++ : $updated++;

                $first['has_sku'] === self::SKU_YES
                    ? $this->saveSkuVariants($product, $productRows)
                    : $this->saveSingleVariant($product, (int) $first['quantity']);
            }
        });

        return ImportResult::success($created, $updated);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array<int, array<string, string>>>
     */
    private function groupByProductCode(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row['product_code']][] = $row;
        }

        return $grouped;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function saveSkuVariants(Product $product, array $rows): void
    {
        foreach ($rows as $row) {
            $variant = $product->variants()->firstWhere('branch_code', $row['branch_code']);

            if ($variant === null) {
                // CSVにサイズ・カラーの列が無いため、新規のバリエーションは規格なしで作る
                $variant = $product->variants()->create([
                    'branch_code' => $row['branch_code'],
                    'sku_code' => $product->product_code.'-'.$row['branch_code'],
                    'size_name' => null,
                    'color_name' => null,
                    'is_available' => true,
                ]);
            } else {
                $variant->update([
                    'sku_code' => $product->product_code.'-'.$row['branch_code'],
                    'is_available' => true,
                ]);
            }

            $this->saveStock($variant, (int) $row['quantity']);
        }
    }

    private function saveSingleVariant(Product $product, int $quantity): void
    {
        $variant = $product->variants()->updateOrCreate(
            ['size_name' => null, 'color_name' => null],
            [
                'branch_code' => null,
                'sku_code' => $product->product_code,
                'is_available' => true,
            ],
        );

        $this->saveStock($variant, $quantity);
    }

    private function saveStock(ProductVariant $variant, int $quantity): void
    {
        Stock::query()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            ['quantity' => $quantity],
        );
    }
}
