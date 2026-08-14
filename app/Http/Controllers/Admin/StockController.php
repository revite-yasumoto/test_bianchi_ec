<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Stock\BuildStockFilter;
use App\Actions\Admin\Stock\BulkUpdateStocks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Stock\BulkUpdateStockRequest;
use App\Http\Requests\Admin\Stock\UpdateStockRequest;
use App\Models\Category;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    private const PER_PAGE = 100;

    /** SKU区分の絞り込みに許可する値 */
    private const HAS_SKU_OPTIONS = ['all', 'with', 'without'];

    public function __construct(private readonly BuildStockFilter $buildFilter) {}

    public function index(Request $request): Response
    {
        $filters = $this->filtersOf($request);

        $stocks = ($this->buildFilter)($filters)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Stock $stock): array => [
                'stock_id' => $stock->id,
                'product_name' => $stock->variant->product->name,
                'category_name' => $stock->variant->product->category->name,
                'variant_label' => $this->variantLabel($stock),
                'sku_code' => $stock->variant->sku_code ?? '-',
                'has_sku' => $stock->variant->product->has_sku,
                'quantity' => $stock->quantity,
            ]);

        return Inertia::render('admin/Stock/Index', [
            'stocks' => $stocks,
            'categories' => $this->categoryOptions(),
            'filters' => $filters,
            'totalCount' => Stock::query()->count(),
        ]);
    }

    public function update(UpdateStockRequest $request, Stock $stock): RedirectResponse
    {
        $stock->update(['quantity' => $request->integer('quantity')]);

        return back();
    }

    public function bulkUpdate(BulkUpdateStockRequest $request, BulkUpdateStocks $bulkUpdate): RedirectResponse
    {
        /** @var array<int, array{id: int|string, quantity: int|string}> $submitted */
        $submitted = $request->validated('stocks');

        if (! $this->matchesCurrentPage($request, $submitted)) {
            return back()->withErrors([
                'stocks' => '表示内容が変わっています。一覧を再読み込みしてからやり直してください。',
            ]);
        }

        $bulkUpdate($submitted);

        return back();
    }

    /**
     * 送信された在庫が、同じ絞り込み条件・同じページの結果と一致するかを再検証する。
     * クライアントが任意のIDを混ぜても、表示していない在庫が書き換わらないようにする。
     *
     * @param  array<int, array{id: int|string, quantity: int|string}>  $submitted
     */
    private function matchesCurrentPage(BulkUpdateStockRequest $request, array $submitted): bool
    {
        $expected = ($this->buildFilter)($this->filtersOf($request))
            ->paginate(self::PER_PAGE, ['*'], 'page', $request->integer('page', 1))
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->sort()
            ->values()
            ->all();

        $actual = collect($submitted)
            ->map(fn (array $stock): int => (int) $stock['id'])
            ->sort()
            ->values()
            ->all();

        return $expected === $actual;
    }

    private function variantLabel(Stock $stock): string
    {
        $parts = array_filter([
            $stock->variant->color_name,
            $stock->variant->size_name,
        ]);

        return $parts === [] ? '規格なし' : implode(' / ', $parts);
    }

    /**
     * @return array{has_sku: string, category_id: int|null, q: string|null}
     */
    private function filtersOf(Request $request): array
    {
        $hasSku = (string) $request->input('has_sku', 'all');

        return [
            'has_sku' => in_array($hasSku, self::HAS_SKU_OPTIONS, true) ? $hasSku : 'all',
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : null,
            'q' => $request->filled('q') ? $request->string('q')->toString() : null,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }
}
