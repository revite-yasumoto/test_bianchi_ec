<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SpecOptionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SpecOption\StoreSpecOptionRequest;
use App\Models\SpecOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SpecOptionController extends Controller
{
    public function index(): Response
    {
        $options = SpecOption::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/SpecOption/Index', [
            'sizes' => $this->rowsOf($options, SpecOptionType::Size),
            'colors' => $this->rowsOf($options, SpecOptionType::Color),
        ]);
    }

    public function store(StoreSpecOptionRequest $request): RedirectResponse
    {
        $type = $request->enum('type', SpecOptionType::class);

        SpecOption::query()->create([
            'type' => $type,
            'name' => $request->string('name')->toString(),
            'sort_order' => (int) SpecOption::query()->where('type', $type->value)->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.spec-options.index');
    }

    public function destroy(SpecOption $specOption): RedirectResponse
    {
        // product_variants は規格値を文字列で保持しており外部キーではないため、既存商品のSKUは壊れない
        $specOption->delete();

        return redirect()->route('admin.spec-options.index');
    }

    /**
     * @param  Collection<int, SpecOption>  $options
     * @return array<int, array{id: int, name: string}>
     */
    private function rowsOf(Collection $options, SpecOptionType $type): array
    {
        return $options
            ->where('type', $type)
            ->map(fn (SpecOption $option): array => [
                'id' => $option->id,
                'name' => $option->name,
            ])
            ->values()
            ->all();
    }
}
